@extends('layouts.admin')

@section('title', 'Faculty Profile & Subject Load')
@section('pageDescription', 'View detailed faculty information and currently assigned subjects.')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('admin.faculty') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Directory
        </a>

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Faculty Details --}}
            <div class="lg:col-span-1 space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center text-center">
                    @if($user->profile_photo)
                        <img src="{{ route('file.show', ['type' => 'profile_image', 'id' => $user->id]) }}" alt="Avatar" class="h-28 w-28 rounded-full object-cover border-4 border-slate-50 shadow-md mb-4">
                    @else
                        @php
                            $initials = collect(explode(' ', trim($user->name)))->map(fn($s) => strtoupper(substr($s, 0, 1)))->take(2)->join('');
                        @endphp
                        <div class="h-28 w-28 rounded-full bg-emerald-100 border-4 border-slate-50 shadow-md flex items-center justify-center text-3xl font-bold text-emerald-600 mb-4">
                            {{ $initials }}
                        </div>
                    @endif

                    <h2 class="text-2xl font-bold text-slate-900">{{ $user->title ? $user->title . ' ' : '' }}{{ $user->name }}</h2>
                    <p class="text-sm font-semibold text-slate-500 mt-1">{{ $user->email }}</p>

                    <div class="flex items-center justify-center gap-2 mt-4">
                        @if($user->status === 'active')
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 uppercase tracking-wider border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-600 uppercase tracking-wider border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive
                            </span>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.faculty.toggle-status', $user) }}" onsubmit="return confirm('Are you sure you want to change this account\'s status?');" class="w-full mt-6">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'inactive' : 'active' }}">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition {{ $user->status === 'active' ? 'bg-white border border-rose-200 text-rose-600 hover:border-rose-300 hover:bg-rose-50' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                            @if($user->status === 'active')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                Deactivate Account
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Activate Account
                            @endif
                        </button>
                    </form>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 p-4">
                        <h3 class="font-bold text-slate-800">Profile Information</h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Department / Level</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->department ?: 'Unassigned' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Official Designation</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->designation ?: 'Faculty Member' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Office Hours</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->office_hours ?: 'Not specified' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Created</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->created_at?->format('F j, Y') ?? 'N/A' }}</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Right Column: Subject Load --}}
            <div class="lg:col-span-2 space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Assigned Subjects (Teaching Load)</h3>
                                <p class="text-xs font-semibold text-slate-500 mt-0.5">Classes currently assigned to this faculty.</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700">{{ $classrooms->count() }} Classes</span>
                    </div>

                    @if($classrooms->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 text-center">
                            <svg class="w-8 h-8 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="text-sm font-semibold text-slate-700">No subjects assigned</p>
                            <p class="text-xs text-slate-500 mt-1">This faculty member does not have any active classrooms.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($classrooms as $classroom)
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 group hover:bg-white hover:border-sky-200 hover:shadow-sm transition">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-bold text-slate-900 group-hover:text-sky-700 transition">{{ $classroom->name }}</h4>
                                            @if($classroom->status === 'active')
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Active</span>
                                            @else
                                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">Inactive</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-2">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                {{ $classroom->code }}
                                            </span>
                                            @if($classroom->schedule)
                                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    {{ $classroom->schedule }}
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                                {{ $classroom->students_count }} Students
                                            </span>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.classrooms.show', $classroom) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:border-sky-300 hover:bg-sky-50 transition shrink-0">
                                        View Class
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
