@extends('layouts.admin')
@section('title', 'System Settings')
@section('pageDescription', 'Configure school information, academic term, and platform settings.')
@section('content')
@php
    $allowedTabs = ['general', 'academic', 'grading', 'appearance', 'password'];
    $requestedTab = (string) request()->query('tab', '');
    $tab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'general';
    $forcePasswordChange = session('force_password_change', false);
    $activeTab = $forcePasswordChange ? 'password' : $tab;
@endphp
<div class="space-y-6" x-data="{ tab: '{{ $activeTab }}' }">
    @if (! $forcePasswordChange)
    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-2 flex gap-2 flex-wrap">
        @foreach(['general'=>'General','academic'=>'Academic Term','grading'=>'Grading','appearance'=>'Appearance','password'=>'Password'] as $k=>$l)
            <button @click="tab='{{ $k }}'" :class="tab==='{{ $k }}'?'bg-green-600 text-white shadow-sm':'text-slate-600 hover:bg-slate-100'" class="rounded-2xl px-5 py-2.5 text-sm font-semibold transition">{{ $l }}</button>
        @endforeach
    </section>

    {{-- General --}}
    <div x-show="tab==='general'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">School Information</h3>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">School / Institution Name</label>
                        <div class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600 font-bold">
                            INFOTECH COLLEGE OF ARTS AND SCIENCES - MARCOS HIGHWAY
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400 italic">This is a static value and cannot be changed.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Timezone</label>
                        <select name="timezone" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="Asia/Manila" @selected($schoolSettings['timezone']==='Asia/Manila')>Asia/Manila (UTC+8)</option>
                            <option value="UTC" @selected($schoolSettings['timezone']==='UTC')>UTC (Universal Coordinated Time)</option>
                            <option value="Asia/Singapore" @selected($schoolSettings['timezone']==='Asia/Singapore')>Asia/Singapore (UTC+8)</option>
                            <option value="Asia/Hong_Kong" @selected($schoolSettings['timezone']==='Asia/Hong_Kong')>Asia/Hong_Kong (UTC+8)</option>
                            <option value="Asia/Tokyo" @selected($schoolSettings['timezone']==='Asia/Tokyo')>Asia/Tokyo (UTC+9)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="rounded-2xl bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition shadow-lg shadow-green-200/50">Save General Settings</button>
            </form>
        </section>
    </div>

    {{-- Academic Term --}}
    <div x-show="tab==='academic'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Academic Term Settings</h3>
            <form id="academic-term-form" method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Academic Year</label>
                        <input name="academic_year" data-term-setting type="text" value="{{ $schoolSettings['academic_year'] }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Current Semester</label>
                        <select name="current_semester" data-term-setting class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="First Semester" @selected($schoolSettings['semester']==='First Semester')>First Semester</option>
                            <option value="Second Semester" @selected($schoolSettings['semester']==='Second Semester')>Second Semester</option>
                            <option value="Third Semester" @selected($schoolSettings['semester']==='Third Semester')>Third Semester</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Grading Period</label>
                        <select name="grading_period" data-term-setting class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="PRELIM" @selected($schoolSettings['grading_period']==='PRELIM')>PRELIM</option>
                            <option value="MIDTERM" @selected($schoolSettings['grading_period']==='MIDTERM')>MIDTERM</option>
                            <option value="FINAL" @selected($schoolSettings['grading_period']==='FINAL')>FINAL</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Final Exam Start Date</label>
                        <input name="final_exam_start" type="date" value="{{ $schoolSettings['exam_start'] }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                </div>
                <button type="submit" class="rounded-2xl bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">Save Term Settings</button>
            </form>

            <div id="term-confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
                <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-500">Confirm rollover</p>
                        <h4 class="mt-2 text-xl font-bold text-slate-900">Apply Academic Term Changes?</h4>
                    </div>
                    <div class="px-6 py-5 text-sm text-slate-600">
                        Changing the School Year, Semester, or Grading Period updates the active dashboard context. This action is non-destructive and preserves prior records.
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                        <button id="cancel-term-change-btn" type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button id="confirm-term-change-btn" type="button" class="rounded-2xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Yes, Confirm Changes</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Grading --}}
    <div x-show="tab==='grading'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Grading Standards</h3>
            <p class="text-sm text-slate-500 mb-6">These are the school-wide grading standards. Faculty members define their own criteria per classroom.</p>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Static Passing Grade --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Passing Grade (%)</label>
                        <div class="relative">
                            <input type="number" value="75" readonly disabled
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600 cursor-not-allowed">
                            <div class="absolute inset-y-0 right-4 flex items-center">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Locked — 75% is the institutional standard. Students scoring below this are flagged as "Failed".</p>
                    </div>

                    {{-- Grading Scale --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Grading Scale</label>
                        <input type="hidden" name="grading_scale" value="gpa">
                        <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Static GPA (Primary)</div>
                        <p class="mt-1.5 text-xs text-slate-400">The GPA scale is used to convert percentage grades across all portals.</p>
                    </div>
                </div>

                {{-- Grade Equivalency Table --}}
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-8 w-8 rounded-xl bg-green-100 text-green-600 grid place-items-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-700">Grade Equivalency (GPA)</p>
                            <p class="text-xs text-slate-400">This table is used globally by Faculty for auto-computation of GPA grades.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="text-sm min-w-full">
                            <thead>
                                <tr class="text-slate-500 text-xs uppercase border-b border-slate-200">
                                    <th class="py-2.5 pr-6 text-left font-semibold">GPA</th>
                                    <th class="py-2.5 pr-6 text-left font-semibold">Percent Range</th>
                                    <th class="py-2.5 text-left font-semibold">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($schoolSettings['grade_equivalency'] as $row)
                                    @php
                                        $isDropped = $row['gpa'] === 'Dropped';
                                        $isPassing = !$isDropped;
                                        // Extract the lower bound from the range string
                                        $rangeParts = explode('-', $row['range']);
                                        $lowerBound = (int) trim($rangeParts[0]);
                                        $passFail = $lowerBound >= 75 ? 'Passed' : ($isDropped ? 'Dropped' : 'Failed');
                                        $rowColor = match($passFail) {
                                            'Passed' => 'text-emerald-700',
                                            'Dropped' => 'text-slate-400',
                                            default => 'text-rose-600',
                                        };
                                    @endphp
                                    <tr class="hover:bg-white transition-colors">
                                        <td class="py-2.5 pr-6 font-bold text-slate-900">{{ $row['gpa'] }}</td>
                                        <td class="py-2.5 pr-6 text-slate-600">{{ $row['range'] }}</td>
                                        <td class="py-2.5">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $passFail === 'Passed' ? 'bg-emerald-100 text-emerald-700' : ($passFail === 'Dropped' ? 'bg-slate-100 text-slate-400' : 'bg-rose-100 text-rose-600') }}">
                                                {{ $passFail }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Info Card --}}
                <div class="rounded-2xl bg-sky-50 border border-sky-200 p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-sky-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="text-sm font-semibold text-sky-800">Grading Criteria</p>
                        <p class="text-xs text-sky-700 mt-0.5">
                            Grading criteria (Quizzes, Exams, Assignments, etc.) are now managed by <strong>Faculty</strong> on a per-classroom basis.
                            Faculty can configure their own component weights in the <strong>Grade Management → Grades</strong> tab.
                        </p>
                    </div>
                </div>

                <button type="submit" class="rounded-2xl bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">Save Grading Settings</button>
            </form>
        </section>
    </div>

    {{-- Appearance --}}
    <div x-show="tab==='appearance'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Appearance</h3>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf
                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                    <p class="font-semibold text-slate-900 text-sm mb-1">Portal Color Theme</p>
                    <p class="text-xs text-slate-500 mb-3">Change the primary color for each portal.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold">Admin Portal Primary</label>
                            <input type="color" name="theme_admin_color" value="{{ $schoolSettings['theme_admin_color'] }}" class="w-full h-10 rounded-md border">
                        </div>
                        <div>
                            <label class="text-xs font-semibold">Faculty Portal Primary</label>
                            <input type="color" name="theme_faculty_color" value="{{ $schoolSettings['theme_faculty_color'] }}" class="w-full h-10 rounded-md border">
                        </div>
                        <div>
                            <label class="text-xs font-semibold">Student Portal Primary</label>
                            <input type="color" name="theme_student_color" value="{{ $schoolSettings['theme_student_color'] }}" class="w-full h-10 rounded-md border">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 border border-slate-100 px-4 py-3.5">
                    <div>
                        <p class="font-semibold text-slate-900 text-sm">Compact Sidebar</p>
                        <p class="text-xs text-slate-500 mt-0.5">Show only icons in the sidebar for more content space.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="compact_sidebar" class="sr-only peer" {{ old('compact_sidebar', false) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition peer-checked:bg-green-600"></div>
                    </label>
                </div>
                <div class="mt-5">
                    <button type="submit" class="rounded-2xl bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">Save Appearance</button>
                </div>
            </form>
        </section>
    </div>

    @endif

    {{-- Password Management --}}
    <div x-show="tab==='password'" x-cloak>
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Password Management</h3>
            <p class="text-sm text-slate-500 mb-6">Update your administrator credentials. A secure password is required to maintain system integrity.</p>

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

            <form method="POST" action="{{ route('admin.settings.password.update') }}" class="max-w-xl space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Current Password</label>
                    <div class="relative">
                        <input id="admin-current-password" name="current_password" type="password" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 @error('current_password') border-rose-500 @enderror">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-password-toggle="admin-current-password" aria-label="Show password">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-slate-400">You must provide your existing password to authorize this change.</p>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">New Password</label>
                    <div class="relative">
                        <input id="admin-new-password" name="password" type="password" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 @error('password') border-rose-500 @enderror">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-password-toggle="admin-new-password" aria-label="Show password">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                    <div class="relative">
                        <input id="admin-confirm-password" name="password_confirmation" type="password" required
                               class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-password-toggle="admin-confirm-password" aria-label="Show password">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="pt-4 flex items-center gap-4">
                    <button type="submit" class="rounded-2xl bg-green-600 px-8 py-3 text-sm font-semibold text-white hover:bg-green-700 transition shadow-lg shadow-green-200/50">Update Password</button>
                    <div class="text-xs text-slate-400 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        SSL Secured Encryption
                    </div>
                </div>
            </form>
        </section>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const termForm = document.getElementById('academic-term-form');
        const modal = document.getElementById('term-confirm-modal');
        const confirmBtn = document.getElementById('confirm-term-change-btn');
        const cancelBtn = document.getElementById('cancel-term-change-btn');

        if (termForm && modal && confirmBtn && cancelBtn) {
            let hasTermChange = false;

            termForm.querySelectorAll('[data-term-setting]').forEach(function (field) {
                field.addEventListener('change', function () {
                    hasTermChange = true;
                });
            });

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            termForm.addEventListener('submit', function (event) {
                if (!hasTermChange) {
                    return;
                }

                event.preventDefault();
                openModal();

                confirmBtn.onclick = function () {
                    closeModal();
                    hasTermChange = false;
                    termForm.submit();
                };

                cancelBtn.onclick = function () {
                    closeModal();
                };
            });
        }

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
