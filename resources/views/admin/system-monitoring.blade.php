@extends('layouts.admin')
@section('title', 'System Monitoring')
@section('pageDescription', 'Real-time server health, usage analytics, and platform statistics.')

@section('content')
<div class="space-y-6" id="system-monitoring">
    {{-- Live header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">System Monitoring</h2>
            <p class="text-sm text-slate-500 mt-0.5">Real-time server diagnostics &amp; platform health</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="last-update" class="text-xs text-slate-400 font-medium"></span>
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Live</span>
        </div>
    </div>

    {{-- Platform Quick Stats --}}
    <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-5">
        @php
        $icons = [
            'users'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"></path></svg>',
            'activity' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
            'classroom'=> '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7z"></path></svg>',
            'check'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'doc'      => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
            'chat'     => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>',
        ];
        $platformKeys = ['users', 'classroom', 'check', 'doc', 'chat'];
        @endphp
        @foreach($platformStats as $idx => $stat)
            <div class="rounded-3xl bg-white border border-slate-200 shadow-sm p-5 hover:shadow-md hover:border-green-200 transition-all">
                <div class="h-9 w-9 rounded-2xl bg-green-100 text-green-600 grid place-items-center mb-3">
                    {!! $icons[$stat['icon']] !!}
                </div>
                <p class="text-2xl font-black text-slate-900 platform-stat" data-key="{{ $platformKeys[$idx] ?? '' }}">{{ $stat['value'] }}</p>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        {{-- Server Stats --}}
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-slate-900">Server Resources</h3>
                <span class="text-xs text-slate-400 font-medium" id="refresh-countdown"></span>
            </div>
            <div class="grid gap-5 sm:grid-cols-2" id="server-stats-grid">
                @foreach($serverStats as $idx => $s)
                    @php
                        $clr = match($s['color']){
                            'emerald' => ['ring'=>'ring-emerald-500','text'=>'text-emerald-600','bg'=>'bg-emerald-500','badge'=>'bg-emerald-100 text-emerald-700','track'=>'emerald'],
                            'amber'   => ['ring'=>'ring-amber-400', 'text'=>'text-amber-600', 'bg'=>'bg-amber-400', 'badge'=>'bg-amber-100 text-amber-700','track'=>'amber'],
                            'sky'     => ['ring'=>'ring-sky-500',   'text'=>'text-sky-600',   'bg'=>'bg-sky-500',   'badge'=>'bg-sky-100 text-sky-700','track'=>'sky'],
                            'violet'  => ['ring'=>'ring-violet-500','text'=>'text-violet-600','bg'=>'bg-violet-500','badge'=>'bg-violet-100 text-violet-700','track'=>'violet'],
                            'rose'    => ['ring'=>'ring-rose-500',  'text'=>'text-rose-600',  'bg'=>'bg-rose-500',  'badge'=>'bg-rose-100 text-rose-700','track'=>'rose'],
                            default   => ['ring'=>'ring-slate-400', 'text'=>'text-slate-600', 'bg'=>'bg-slate-400', 'badge'=>'bg-slate-100 text-slate-600','track'=>'slate'],
                        };
                        $pct = is_numeric($s['value']) && $s['unit']==='%' ? (int)$s['value'] : null;
                    @endphp
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5 server-stat-card" data-index="{{ $idx }}">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-slate-700">{{ $s['label'] }}</p>
                            <span class="stat-badge inline-flex rounded-full {{ $clr['badge'] }} px-2.5 py-0.5 text-xs font-bold transition-all">{{ $s['status'] }}</span>
                        </div>
                        <p class="text-3xl font-black stat-value {{ $clr['text'] }} transition-all">{{ $s['value'] }}<span class="text-lg font-semibold text-slate-400 ml-0.5">{{ $s['unit'] }}</span></p>
                        <p class="text-xs text-slate-400 mt-1 stat-detail">{{ $s['detail'] ?? '' }}</p>
                        @if($pct !== null)
                            <div class="mt-3 h-2.5 w-full rounded-full bg-slate-200 overflow-hidden">
                                <div class="stat-bar h-full rounded-full {{ $clr['bg'] }} transition-all duration-1000 ease-out" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Health Checks --}}
        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-5">System Health</h3>
            <div class="space-y-3" id="health-checks">
                @foreach($healthChecks as $idx => $check)
                    <div class="health-row flex items-center gap-4 rounded-2xl {{ $check['status']==='ok' ? 'bg-emerald-50 border border-emerald-100' : ($check['status']==='warning' ? 'bg-amber-50 border border-amber-200' : 'bg-rose-50 border border-rose-200') }} px-4 py-3 transition-all" data-index="{{ $idx }}">
                        <div class="h-8 w-8 flex-shrink-0 rounded-full {{ $check['status']==='ok' ? 'bg-emerald-100' : ($check['status']==='warning' ? 'bg-amber-100' : 'bg-rose-100') }} grid place-items-center">
                            @if($check['status']==='ok')
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="health-name text-sm font-semibold {{ $check['status']==='ok' ? 'text-emerald-900' : 'text-amber-900' }}">{{ $check['name'] }}</p>
                            <p class="health-detail text-xs {{ $check['status']==='ok' ? 'text-emerald-600' : 'text-amber-700' }}">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Registration Trend Chart --}}
    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Registration Trend</h3>
        <p class="text-sm text-slate-500 mb-6">New user registrations per month (students &amp; faculty).</p>
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

{{-- Real-time polling script --}}
<script>
(function() {
    const API_URL = @json(route('admin.system-monitoring.api'));
    const POLL_INTERVAL = 5000; // 5 seconds
    let countdown = 5;
    let timer = null;
    let countdownTimer = null;

    const colorMap = {
        emerald: { text: 'text-emerald-600', bg: 'bg-emerald-500', badge: 'bg-emerald-100 text-emerald-700' },
        amber:   { text: 'text-amber-600',   bg: 'bg-amber-400',  badge: 'bg-amber-100 text-amber-700' },
        sky:     { text: 'text-sky-600',     bg: 'bg-sky-500',    badge: 'bg-sky-100 text-sky-700' },
        violet:  { text: 'text-violet-600',  bg: 'bg-violet-500', badge: 'bg-violet-100 text-violet-700' },
        rose:    { text: 'text-rose-600',    bg: 'bg-rose-500',   badge: 'bg-rose-100 text-rose-700' },
        slate:   { text: 'text-slate-600',   bg: 'bg-slate-400',  badge: 'bg-slate-100 text-slate-600' },
    };

    const platformKeyMap = ['users', 'classrooms', 'attendance', 'documents', 'forum'];

    function updateCountdown() {
        const el = document.getElementById('refresh-countdown');
        if (el) el.textContent = 'Refreshing in ' + countdown + 's';
        countdown--;
        if (countdown < 0) countdown = Math.floor(POLL_INTERVAL / 1000);
    }

    function animateValue(el, newVal) {
        const current = el.textContent.trim();
        if (current !== String(newVal)) {
            el.style.transform = 'scale(1.08)';
            el.style.opacity = '0.6';
            setTimeout(function() {
                el.style.transform = 'scale(1)';
                el.style.opacity = '1';
            }, 300);
        }
    }

    function updateServerStats(stats) {
        stats.forEach(function(stat, idx) {
            const card = document.querySelector('.server-stat-card[data-index="' + idx + '"]');
            if (!card) return;

            // Update value
            const valueEl = card.querySelector('.stat-value');
            if (valueEl) {
                const newHtml = stat.value + '<span class="text-lg font-semibold text-slate-400 ml-0.5">' + stat.unit + '</span>';
                animateValue(valueEl, stat.value);
                // Clear old color classes and apply new
                valueEl.className = valueEl.className.replace(/text-\w+-\d+/g, '');
                const colors = colorMap[stat.color] || colorMap.slate;
                valueEl.classList.add(...colors.text.split(' '));
                valueEl.innerHTML = newHtml;
            }

            // Update badge
            const badge = card.querySelector('.stat-badge');
            if (badge) {
                badge.className = 'stat-badge inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold transition-all';
                const colors = colorMap[stat.color] || colorMap.slate;
                badge.classList.add(...colors.badge.split(' '));
                badge.textContent = stat.status;
            }

            // Update detail
            const detail = card.querySelector('.stat-detail');
            if (detail && stat.detail) {
                detail.textContent = stat.detail;
            }

            // Update progress bar
            const bar = card.querySelector('.stat-bar');
            if (bar && stat.unit === '%') {
                bar.style.width = stat.value + '%';
                // Update bar color
                bar.className = 'stat-bar h-full rounded-full transition-all duration-1000 ease-out';
                const colors = colorMap[stat.color] || colorMap.slate;
                bar.classList.add(colors.bg);
            }
        });
    }

    function updatePlatformStats(stats) {
        var keyOrder = ['users', 'classrooms', 'attendance', 'documents', 'forum'];
        document.querySelectorAll('.platform-stat').forEach(function(el) {
            var key = el.dataset.key;
            // Map display keys to API keys
            var apiKey = key;
            if (key === 'classroom') apiKey = 'classrooms';
            if (key === 'check') apiKey = 'attendance';
            if (key === 'doc') apiKey = 'documents';
            if (key === 'chat') apiKey = 'forum';
            if (stats[apiKey] !== undefined) {
                var newVal = String(stats[apiKey]);
                if (el.textContent.trim() !== newVal) {
                    animateValue(el, newVal);
                    setTimeout(function() { el.textContent = newVal; }, 150);
                }
            }
        });
    }

    function updateHealthChecks(checks) {
        checks.forEach(function(check, idx) {
            const row = document.querySelector('.health-row[data-index="' + idx + '"]');
            if (!row) return;

            const isOk = check.status === 'ok';
            const isWarning = check.status === 'warning';

            // Update row classes
            row.className = 'health-row flex items-center gap-4 rounded-2xl px-4 py-3 transition-all';
            if (isOk) {
                row.classList.add('bg-emerald-50', 'border', 'border-emerald-100');
            } else if (isWarning) {
                row.classList.add('bg-amber-50', 'border', 'border-amber-200');
            } else {
                row.classList.add('bg-rose-50', 'border', 'border-rose-200');
            }

            // Update detail text
            const detail = row.querySelector('.health-detail');
            if (detail) detail.textContent = check.detail;
        });
    }

    function poll() {
        fetch(API_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.serverStats) updateServerStats(data.serverStats);
            if (data.platformStats) updatePlatformStats(data.platformStats);
            if (data.healthChecks) updateHealthChecks(data.healthChecks);

            var updateEl = document.getElementById('last-update');
            if (updateEl && data.timestamp) {
                updateEl.textContent = 'Updated ' + data.timestamp;
            }
        })
        .catch(function(err) {
            console.warn('System monitoring poll failed:', err);
        });

        countdown = Math.floor(POLL_INTERVAL / 1000);
    }

    // Initial countdown display
    updateCountdown();

    // Start polling
    timer = setInterval(poll, POLL_INTERVAL);
    countdownTimer = setInterval(updateCountdown, 1000);

    // Set initial timestamp
    var updateEl = document.getElementById('last-update');
    if (updateEl) updateEl.textContent = 'Updated {{ now()->format("h:i:s A") }}';

    // Pause polling when tab is hidden to save resources
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(timer);
            clearInterval(countdownTimer);
        } else {
            poll(); // Immediate refresh when coming back
            timer = setInterval(poll, POLL_INTERVAL);
            countdownTimer = setInterval(updateCountdown, 1000);
        }
    });
})();
</script>

<style>
    .stat-value { transition: transform 0.3s ease, opacity 0.3s ease, color 0.5s ease; }
    .stat-bar { transition: width 1s ease-out, background-color 0.5s ease; }
    .stat-badge { transition: background-color 0.5s ease, color 0.5s ease; }
    .platform-stat { transition: transform 0.3s ease, opacity 0.3s ease; }
    .health-row { transition: background-color 0.5s ease, border-color 0.5s ease; }

    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    }
</style>
@endsection
