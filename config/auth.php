<?php

use App\Models\User\User;
use App\Models\Admin\Admin;
use App\Models\Driver\Driver;

return [

    'defaults' => [
        'guard'     => 'users',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'users' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'admin' => [
            'driver'   => 'session',
            'provider' => 'admins',
        ],

        'driver' => [
            'driver'   => 'session',
            'provider' => 'drivers',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => User::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model'  => Admin::class,
        ],

        'drivers' => [
            'driver' => 'eloquent',
            'model'  => Driver::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
