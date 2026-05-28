<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Under Maintenance | ICAS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-xl w-full rounded-3xl border border-slate-200 bg-white p-8 shadow-sm text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">System Under Maintenance</h1>
            <p class="mt-3 text-sm text-slate-600">The Student and Faculty portals are temporarily unavailable while we perform scheduled maintenance.</p>
            <p class="mt-2 text-xs text-slate-500">Please check back shortly. If you need urgent assistance, contact your administrator.</p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <a href="{{ route('home') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Return to Homepage</a>
                <a href="{{ route('login') }}" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
