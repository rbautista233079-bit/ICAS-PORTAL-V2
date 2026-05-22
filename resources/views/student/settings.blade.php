@extends('layouts.student')
@section('title', 'Settings')
@section('pageDescription', 'Manage your account preferences, password, and notification settings.')
@section('content')
@php
    $allowedTabs = ['account', 'password', 'notifications', 'privacy'];
    $requestedTab = (string) request()->query('tab', '');
    $tab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'account';
    $forcePasswordChange = session('force_password_change', false);
    $activeTab = $forcePasswordChange ? 'password' : $tab;
@endphp
<div class="space-y-6" x-data="{ tab: '{{ $activeTab }}' }">
    @if (! $forcePasswordChange)
    {{-- Tab Nav --}}
    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-2 flex gap-2 flex-wrap">
        @foreach(['account' => 'Account', 'password' => 'Password', 'notifications' => 'Notifications', 'privacy' => 'Privacy'] as $key => $label)
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-green-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    class="rounded-2xl px-5 py-2.5 text-sm font-semibold transition">
                {{ $label }}
            </button>
        @endforeach
    </section>

    {{-- Account Tab --}}
    <div x-show="tab === 'account'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Account Information</h3>
            <form class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Full Name</label>
                        <input type="text" value="{{ $user->name }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" value="{{ $user->email }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="tel" value="+63 912 345 6789" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Home Address</label>
                        <input type="text" value="123 University Ave, Manila" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="rounded-2xl bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">Save Changes</button>
                </div>
            </form>
        </section>
    </div>

    @endif

    {{-- Password Tab --}}
    <div x-show="tab === 'password'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Change Password</h3>
            @if($forcePasswordChange)
                <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm font-semibold">
                    {{ session('force_password_change_message', 'For your security, please change your password before proceeding.') }}
                </div>
            @endif
            @if(session('status'))
                <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-700 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('student.settings.password') }}" class="space-y-5 max-w-md">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Current Password</label>
                    <div class="relative">
                        <input id="student-current-password" type="password" name="current_password" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent @error('current_password') border-rose-500 @enderror" placeholder="••••••••">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-password-toggle="student-current-password" aria-label="Show password">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                    @error('current_password') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">New Password</label>
                    <div class="relative">
                        <input id="student-new-password" type="password" name="new_password" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent @error('new_password') border-rose-500 @enderror" placeholder="••••••••">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-password-toggle="student-new-password" aria-label="Show password">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                    @error('new_password') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                    <div class="relative">
                        <input id="student-confirm-password" type="password" name="new_password_confirmation" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent" placeholder="••••••••">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-password-toggle="student-confirm-password" aria-label="Show password">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>
                
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600">
                    <p class="font-bold text-xs uppercase tracking-widest text-slate-400 mb-2">Password Requirements</p>
                    <ul class="space-y-1 text-xs">
                        <li class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Minimum 8 characters</li>
                        <li class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> At least one uppercase letter</li>
                        <li class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> At least one number & special character</li>
                    </ul>
                </div>
                <button type="submit" class="rounded-2xl bg-green-600 px-8 py-3 text-sm font-bold text-white hover:bg-green-700 transition shadow-lg shadow-green-200/50">Update Password</button>
            </form>
        </section>
    </div>

    @if (! $forcePasswordChange)
    {{-- Notifications Tab --}}
    <div x-show="tab === 'notifications'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Notification Preferences</h3>
            <div class="space-y-4">
                @foreach([
                    ['label' => 'Grade Releases', 'desc' => 'Notify me when a new grade is posted for my subjects.', 'default' => true],
                    ['label' => 'Announcements', 'desc' => 'Notify me of new school-wide and class announcements.', 'default' => true],
                    ['label' => 'Document Requests', 'desc' => 'Notify me when my document request status changes.', 'default' => true],
                    ['label' => 'Enrollment Updates', 'desc' => 'Notify me when my enrollment is approved or changed.', 'default' => true],
                    ['label' => 'Forum Replies', 'desc' => 'Notify me when someone replies to my forum post.', 'default' => false],
                    ['label' => 'Email Digest', 'desc' => 'Receive a daily email summary of unread notifications.', 'default' => false],
                ] as $pref)
                    <div class="flex items-start justify-between gap-4 rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3.5">
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $pref['label'] }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $pref['desc'] }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                            <input type="checkbox" class="sr-only peer" {{ $pref['default'] ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-green-400 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="mt-5">
                <button class="rounded-2xl bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">Save Preferences</button>
            </div>
        </section>
    </div>

    {{-- Privacy Tab --}}
    <div x-show="tab === 'privacy'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Privacy & Data</h3>
            <div class="space-y-4">
                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                    <p class="font-semibold text-slate-900 text-sm mb-1">Profile Visibility</p>
                    <p class="text-xs text-slate-500 mb-3">Control who can see your profile information.</p>
                    <select class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option>Faculty and Administrators only</option>
                        <option>All enrolled students</option>
                    </select>
                </div>
                <div class="rounded-2xl bg-rose-50 border border-rose-200 p-4">
                    <p class="font-semibold text-rose-900 text-sm mb-1">Danger Zone</p>
                    <p class="text-xs text-rose-700 mb-3">Deactivating your account will restrict your access to all portal features. This action must be reviewed by an Administrator.</p>
                    <button class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 transition">Request Account Deactivation</button>
                </div>
            </div>
        </section>
    </div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = btn.getAttribute('data-password-toggle');
                const input = document.getElementById(targetId);
                if (!input) {
                    return;
                }

                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.innerHTML = show
                    ? '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.065 10.065 0 012.132-3.444m2.923-2.108A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.036 10.036 0 01-4.043 5.188M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 9L3 3"></path></svg>'
                    : '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
            });
        });
    });
</script>
@endsection
