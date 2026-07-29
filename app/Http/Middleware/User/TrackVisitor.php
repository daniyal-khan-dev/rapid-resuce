<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\Models\User\VisitorLog;
use App\Events\VisitorLogCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TrackVisitor
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        $ip  = $request->ip();
        $now = Carbon::now();

        $alreadyLogged = VisitorLog::where('ip_address', $ip)
            ->where('created_at', '>=', $now->copy()->subDay())
            ->exists();

        if (!$alreadyLogged) {
            $agent = new Agent();

            $log = VisitorLog::create([
                'ip_address' => $ip,
                'browser'    => $agent->browser(),
                'platform'   => $agent->platform(),
                'device'     => $agent->device(),
                'is_mobile'  => $agent->isMobile(),
                'created_at' => $now,
            ]);

            event(new VisitorLogCreated(
                ip_address: $log->ip_address,
                browser:    $log->browser,
                platform:   $log->platform,
                device:     $log->device,
                is_mobile:  (bool) $log->is_mobile,
                created_at: $now->toISOString(),
            ));
        }

        return $next($request);
    }
}
