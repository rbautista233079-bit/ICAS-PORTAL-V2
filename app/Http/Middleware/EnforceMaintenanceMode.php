<?php

namespace App\Http\Middleware;

use App\Services\SystemSettingsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceMaintenanceMode
{
    public function __construct(private SystemSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (string) $this->settings->get('maintenance_enabled', '0');

        if ($enabled !== '1') {
            return $next($request);
        }

        $untilRaw = $this->settings->get('maintenance_until');
        if ($untilRaw) {
            $until = Carbon::parse($untilRaw);
            if (Carbon::now()->greaterThan($until)) {
                $this->settings->set('maintenance_enabled', '0', 'boolean');
                $this->settings->set('maintenance_until', '', 'string');

                return $next($request);
            }
        }

        $user = Auth::user();
        if ($user && in_array($user->role, ['student', 'faculty'], true)) {
            return redirect()->route('maintenance.notice');
        }

        return $next($request);
    }
}
