@extends('layouts.admin')
@section('title', 'System Monitoring')
@section('pageDescription', 'Monitor server health, usage analytics, and platform statistics.')
@section('content')
<div class="space-y-6">
    {{-- Platform Quick Stats --}}
    <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
        @php
        $icons = [
            'users'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"></path></svg>',
            'activity' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
            'classroom'=> '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7z"></path></svg>',
            'check'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'doc'      => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
            'chat'     => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>',
        ];
        @endphp
        @foreach($platformStats as $stat)
            <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-5">
                <div class="h-9 w-9 rounded-2xl bg-green-100 text-green-600 grid place-items-center mb-3">
                    {!! $icons[$stat['icon']] !!}
                </div>
                <p class="text-2xl font-black text-slate-900">{{ $stat['value'] }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        {{-- Server Stats --}}
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">Server Resources</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach($serverStats as $s)
                    @php
                        $clr = match($s['color']){
                            'emerald' => ['ring'=>'ring-emerald-500','text'=>'text-emerald-600','bg'=>'bg-emerald-500','badge'=>'bg-emerald-100 text-emerald-700'],
                            'amber'   => ['ring'=>'ring-amber-400', 'text'=>'text-amber-600', 'bg'=>'bg-amber-400', 'badge'=>'bg-amber-100 text-amber-700'],
                            'sky'     => ['ring'=>'ring-sky-500',   'text'=>'text-sky-600',   'bg'=>'bg-sky-500',   'badge'=>'bg-sky-100 text-sky-700'],
                            'violet'  => ['ring'=>'ring-violet-500','text'=>'text-violet-600','bg'=>'bg-violet-500','badge'=>'bg-violet-100 text-violet-700'],
                            default   => ['ring'=>'ring-slate-400', 'text'=>'text-slate-600', 'bg'=>'bg-slate-400', 'badge'=>'bg-slate-100 text-slate-600'],
                        };
                        $pct = is_numeric($s['value']) && $s['unit']==='%' ? (int)$s['value'] : 50;
                    @endphp
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold text-slate-700">{{ $s['label'] }}</p>
                            <span class="inline-flex rounded-full {{ $clr['badge'] }} px-2.5 py-0.5 text-xs font-bold">{{ $s['status'] }}</span>
                        </div>
                        <p class="text-3xl font-black {{ $clr['text'] }}">{{ $s['value'] }}<span class="text-lg font-semibold text-slate-400 ml-0.5">{{ $s['unit'] }}</span></p>
                        @if($s['unit'] === '%')
                            <div class="mt-3 h-2.5 w-full rounded-full bg-slate-200">
                                <div class="h-full rounded-full {{ $clr['bg'] }} transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Health Checks --}}
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">System Health</h3>
            <div class="space-y-3">
                @foreach($healthChecks as $check)
                    <div class="flex items-center gap-4 rounded-2xl {{ $check['status']==='ok' ? 'bg-emerald-50 border border-emerald-100' : 'bg-amber-50 border border-amber-200' }} px-4 py-3">
                        <div class="h-8 w-8 flex-shrink-0 rounded-full {{ $check['status']==='ok' ? 'bg-emerald-100' : 'bg-amber-100' }} grid place-items-center">
                            @if($check['status']==='ok')
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold {{ $check['status']==='ok' ? 'text-emerald-900' : 'text-amber-900' }}">{{ $check['name'] }}</p>
                            <p class="text-xs {{ $check['status']==='ok' ? 'text-emerald-600' : 'text-amber-700' }}">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Registration Trend Chart --}}
    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Registration Trend</h3>
        <p class="text-sm text-slate-500 mb-6">New user registrations per month (students & faculty).</p>
        @php
            $maxValue = 0;
            foreach ($registrationTrend as $row) {
                $maxValue = max($maxValue, $row['students'], $row['faculty']);
            }
        @endphp
        <div class="flex items-end gap-4 h-48">
            @foreach($registrationTrend as $row)
                @php
                    $sPct = $maxValue > 0 ? round(($row['students'] / $maxValue) * 100) : 0;
                    $fPct = $maxValue > 0 ? round(($row['faculty'] / $maxValue) * 100) : 0;
                @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex items-end gap-1 h-36">
                        <div class="flex-1 rounded-t-xl bg-green-500 transition-all hover:bg-green-600 cursor-pointer relative group" style="height: {{ $sPct }}%">
                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs font-bold text-green-700 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">{{ $row['students'] }} students</span>
                        </div>
                        <div class="flex-1 rounded-t-xl bg-sky-400 transition-all hover:bg-sky-500 cursor-pointer relative group" style="height: {{ max($fPct, 4) }}%">
                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs font-bold text-sky-700 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">{{ $row['faculty'] }} faculty</span>
                        </div>
                    </div>
                    <p class="text-xs font-semibold text-slate-500">{{ $row['month'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex items-center gap-6 text-xs">
            <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-sm bg-green-500"></div><span class="text-slate-600">Students</span></div>
            <div class="flex items-center gap-2"><div class="h-3 w-3 rounded-sm bg-sky-400"></div><span class="text-slate-600">Faculty</span></div>
        </div>
    </section>
</div>
@endsection
