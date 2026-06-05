@extends('layouts.faculty')
@section('title', 'My Schedule')
@section('pageDescription', 'Your weekly teaching schedule for the current semester.')
@section('content')
<div class="space-y-6">
    <section class="rounded-3xl bg-slate-900 p-6 shadow-md text-white">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Teaching Schedule</h2>
                <p class="mt-1 text-slate-400 text-sm">AY 2024–2025 · Second Semester</p>
            </div>
            <div class="flex gap-6">
                <div class="text-center">
                    <p class="text-3xl font-black text-green-400">4</p>
                    <p class="text-xs text-slate-400">Subjects</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-black text-green-400">{{ $totalStudents }}</p>
                    <p class="text-xs text-slate-400">Students</p>
                </div>
            </div>
        </div>
    </section>

    @if($finalExamStartDate)
        <section class="rounded-3xl bg-amber-50 border border-amber-200 p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 pt-0.5">
                    <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-amber-900">Final Exam Period</p>
                    <p class="text-sm text-amber-800 mt-0.5">Starts on {{ \Carbon\Carbon::parse($finalExamStartDate)->format('M j, Y') }}</p>
                </div>
            </div>
        </section>
    @endif

    @php
        $days = ['Mon','Tue','Wed','Thu','Fri','Sat'];
        $subjectColors = [
            'MATH301' => 'bg-emerald-50 border-emerald-300 text-emerald-900',
            'ENG101'  => 'bg-violet-50 border-violet-300 text-violet-900',
            'PHY201'  => 'bg-sky-50 border-sky-300 text-sky-900',
            'HIST201' => 'bg-amber-50 border-amber-300 text-amber-900',
        ];
        $today = date('D');
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($days as $day)
            <div class="rounded-3xl bg-white border {{ $today === $day ? 'border-slate-900 shadow-md ring-2 ring-slate-200' : 'border-slate-200 shadow-sm' }} overflow-hidden">
                <div class="px-5 py-3.5 flex items-center justify-between {{ $today === $day ? 'bg-slate-900 text-white' : 'bg-slate-50 border-b border-slate-200' }}">
                    <p class="font-bold text-sm">{{ $day }}{{ $today === $day ? ' — Today' : '' }}</p>
                    <span class="text-xs {{ $today === $day ? 'text-slate-400' : 'text-slate-400' }}">{{ count($schedule[$day]) }} class{{ count($schedule[$day]) !== 1 ? 'es' : '' }}</span>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($schedule[$day] as $cls)
                        @php $clr = $subjectColors[$cls['code']] ?? 'bg-slate-50 border-slate-200 text-slate-900'; @endphp
                        <div class="rounded-2xl border {{ $clr }} p-3">
                            <p class="font-bold text-sm">{{ $cls['subject'] }}</p>
                            <p class="text-xs font-mono mt-0.5 opacity-60">{{ $cls['code'] }}</p>
                            <div class="mt-2 space-y-0.5 text-xs opacity-80">
                                <p>⏰ {{ $cls['time'] }}</p>
                                <p>📍 {{ $cls['room'] }}</p>
                                <p>👥 {{ $cls['students'] }} students</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-slate-400 py-6">No classes</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Upcoming Events & Calendar --}}
    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-lg font-bold text-slate-900">School Calendar & Events</h3>
                <p class="text-xs text-slate-400 mt-0.5">Upcoming school-wide and class-specific events</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-bold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ count($events ?? []) }} upcoming
            </span>
        </div>

        @if(!empty($events))
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($events as $event)
                    <div class="rounded-2xl border {{ $event['is_global'] ? 'border-amber-200 bg-amber-50/60' : 'border-slate-200 bg-slate-50/60' }} p-4">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide
                                {{ $event['is_global'] ? 'bg-amber-200 text-amber-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $event['is_global'] ? 'School-wide' : $event['classroom_code'] }}
                            </span>
                            <span class="text-xs text-slate-500 whitespace-nowrap">{{ $event['date'] }}</span>
                        </div>
                        <p class="font-bold text-sm text-slate-900">{{ $event['title'] }}</p>
                        @if($event['description'])
                            <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $event['description'] }}</p>
                        @endif
                        @if($event['classroom_name'] && !$event['is_global'])
                            <p class="text-[11px] text-slate-400 mt-2">📚 {{ $event['classroom_name'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-8 text-center">
                <svg class="mx-auto w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <p class="text-sm text-slate-500">No upcoming events scheduled.</p>
            </div>
        @endif
    </section>
</div>
@endsection
