<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthUser
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('users')->check()) {
            return redirect()->route('home');
        }
        return $next($request);
    }
}
