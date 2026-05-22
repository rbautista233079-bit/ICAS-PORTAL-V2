<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForcePasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $requiresReset = $user->registration_source === 'csv_import' && $user->force_password_reset;

        if (! $requiresReset) {
            $request->session()->forget(['force_password_change', 'force_password_change_message']);

            return $next($request);
        }

        $message = 'For your security, please change your password before proceeding.';
        $request->session()->put('force_password_change', true);
        $request->session()->put('force_password_change_message', $message);

        // Define allowed routes based on role
        $allowedRoutes = ['logout'];
        $targetParams = [];

        if ($user->role === 'admin') {
            $allowedRoutes[] = 'admin.settings';
            $allowedRoutes[] = 'admin.settings.password.update';
            $targetRoute = 'admin.settings';
            $targetParams = ['tab' => 'password'];
        } elseif ($user->role === 'faculty') {
            $allowedRoutes[] = 'faculty.settings';
            $allowedRoutes[] = 'faculty.settings.password';
            $targetRoute = 'faculty.settings';
        } else {
            // Students
            $allowedRoutes[] = 'student.settings';
            $allowedRoutes[] = 'student.settings.password';
            $allowedRoutes[] = 'password.change';
            $allowedRoutes[] = 'password.update';
            $targetRoute = 'student.settings';
            $targetParams = ['tab' => 'password'];
        }

        if ($request->routeIs($allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route($targetRoute, $targetParams)
            ->with('status', $message);
    }
}
