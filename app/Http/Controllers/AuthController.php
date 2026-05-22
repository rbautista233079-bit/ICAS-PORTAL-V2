<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View
    {
        return view('login');
    }

    /**
     * Show the registration form.
     */
    public function showRegister(): View
    {
        return view('register');
    }

    public function showForgotPassword(): View
    {
        return view('forgot-password');
    }

    public function showForgotPasswordSent(Request $request): View
    {
        $email = (string) $request->session()->get('password_reset_email', '');

        return view('forgot-password-sent', compact('email'));
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        return redirect()
            ->route('password.sent')
            ->with('password_reset_email', (string) $request->string('email'));
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    /**
     * Handle the registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $academicLevel = $request->input('academic_level');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:student'], // Restricted to student only
        ];

        $rules['enrollment_type'] = ['required', 'in:New Student,Old Student'];
        $rules['academic_level'] = ['required', 'string'];

        if ($academicLevel === 'Senior High School') {
            $rules['strand'] = ['required', 'in:ICT,HE'];
        } else {
            $rules['course'] = ['required', 'in:BSIT,BSHM'];
        }

        $data = $request->validate($rules);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // User model has 'hashed' cast
            'role' => 'student',
            'enrollment_type' => $data['enrollment_type'],
            'academic_level' => $data['academic_level'],
            'course' => ($data['academic_level'] !== 'Senior High School') ? ($data['course'] ?? null) : null,
            'strand' => ($data['academic_level'] === 'Senior High School') ? ($data['strand'] ?? null) : null,
            'force_password_reset' => false,
            'registration_source' => 'manual',
        ]);

        return redirect()->route('login')
            ->with('status', 'Registration successful! Please log in with your new account.')
            ->withInput(['email' => $data['email'], 'role' => 'student']);
    }

    /**
     * Handle the authentication attempt.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $request->merge(['email' => trim($request->email)]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['required', 'in:student,faculty,admin'],
        ]);

        $throttleKey = Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            return back()->withErrors([
                'email' => "Account locked due to too many failed attempts. Please try again in {$minutes} minute(s).",
            ])->with('lockout_seconds', $seconds)->withInput();
        }

        $selectedRole = $credentials['role'];
        unset($credentials['role']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role !== $selectedRole) {
                Auth::logout();
                RateLimiter::hit($throttleKey, 1800); // 30 mins

                return back()->withErrors([
                    'email' => 'This account is a '.ucfirst($user->role).', not a '.ucfirst($selectedRole).'.',
                ])->withInput();
            }

            if ($user->status === 'inactive') {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ])->withInput();
            }

            if ($user->role === 'student' && $user->status === 'pending') {
                Auth::logout();
                $hasRequiredProof = false;
                     if ($user->enrollment_type === 'New Student' && $user->receipt_proof) {
                    $hasRequiredProof = true;
                     } elseif ($user->enrollment_type === 'Old Student' && $user->student_id_proof) {
                    $hasRequiredProof = true;
                }

                if ($hasRequiredProof) {
                    return back()->withErrors([
                        'email' => 'Your account is under review by an administrator. Please wait for activation.',
                    ])->withInput();
                }

                $request->session()->put('pending_user_id', $user->id);

                return back()->with('show_verification', true)
                    ->with('enrollment_type', $user->enrollment_type)
                    ->withInput();
            }

            // Success: clear rate limiter
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            AuditTrail::log('Login', 'Auth', 'User logged in as '.$selectedRole);

            // Redirection Enforcement for Password Reset
            if ($user->registration_source === 'csv_import' && $user->force_password_reset) {
                $message = 'For your security, please change your password before proceeding.';
                $request->session()->put('force_password_change', true);
                $request->session()->put('force_password_change_message', $message);

                $targetRoute = $user->role.'.settings';
                $targetParams = [];
                if (in_array($user->role, ['admin', 'student'], true)) {
                    $targetParams = ['tab' => 'password'];
                }

                return redirect()->route($targetRoute, $targetParams)
                    ->with('status', $message);
            }

            return match ($selectedRole) {
                'admin' => redirect()->intended(route('admin.dashboard')),
                'faculty' => redirect()->intended(route('faculty.dashboard')),
                'student' => redirect()->intended(route('student.dashboard')),
                default => redirect()->intended('/'),
            };
        }

        // Failure: increment attempts
        RateLimiter::hit($throttleKey, 1800);

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditTrail::log('Logout', 'Auth', 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                // Complexity: 1 uppercase, 1 lowercase, 1 digit, 1 special char
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $user->update([
            'password' => $request->password,
            'force_password_reset' => false,
        ]);

        $request->session()->forget(['force_password_change', 'force_password_change_message']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Password updated successfully. Please log in with your new credentials.');
    }

    public function verifyUpload(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('pending_user_id');
        if (! $userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please try again.']);
        }

        $user = User::find($userId);
        if (! $user || $user->status !== 'pending') {
            return redirect()->route('login')->withErrors(['email' => 'Invalid account state.']);
        }

        if ($user->enrollment_type === 'New Student') {
            $request->validate([
                'receipt_proof' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            ]);

            if ($request->hasFile('receipt_proof')) {
                $file = $request->file('receipt_proof');
                    if ($user->receipt_proof) {
                        Storage::disk('local')->delete($user->receipt_proof);
                    }
                    $user->receipt_proof = $file->store('verifications/receipt-proofs', 'local');
                    $user->receipt_proof_mime = $file->getMimeType();
            }
        } elseif ($user->enrollment_type === 'Old Student') {
            $request->validate([
                'student_id_proof' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            ]);

            if ($request->hasFile('student_id_proof')) {
                $file = $request->file('student_id_proof');
                    if ($user->student_id_proof) {
                        Storage::disk('local')->delete($user->student_id_proof);
                    }
                    $user->student_id_proof = $file->store('verifications/student-ids', 'local');
                    $user->student_id_proof_mime = $file->getMimeType();
            }
        } else {
            return redirect()->route('login')->withErrors(['email' => 'Invalid enrollment type.']);
        }

        $user->save();
        $request->session()->forget('pending_user_id');

        return redirect()->route('login')->with('status', 'Verification documents submitted successfully! Your account is now under review.');
    }
}
