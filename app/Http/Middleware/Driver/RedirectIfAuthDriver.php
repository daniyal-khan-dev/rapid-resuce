<?php

namespace App\Http\Middleware\Driver;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthDriver
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('driver')->check()) {
            return redirect()->route('driver.dashboard');
        }
        return $next($request);
    }
}
