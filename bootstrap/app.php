<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB)
        ;

        $middleware->alias([
            'redirect.auth.admin' => \App\Http\Middleware\Admin\RedirectIfAuthAdmin::class,
            'redirect.auth.driver' => \App\Http\Middleware\Driver\RedirectIfAuthDriver::class,
            'redirect.auth.user'  => \App\Http\Middleware\User\RedirectIfAuthUser::class,
            'track.visitor'       => \App\Http\Middleware\User\TrackVisitor::class,
            'no.cache'            => \App\Http\Middleware\NoCacheMiddleware::class,
        ]);

        // Redirect unauthenticated requests per guard
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin/*') || $request->routeIs('admin.*')) {
                return route('admin.login');
            }
            if ($request->is('driver/*') || $request->routeIs('driver.*')) {
                return route('driver.login');
            }
            return route('login');
        });

        // Redirect already-authenticated requests per guard
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin/*') || $request->routeIs('admin.*')) {
                return route('admin.dashboard');
            }
            if ($request->is('driver/*') || $request->routeIs('driver.*')) {
                return route('driver.dashboard');
            }
            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
