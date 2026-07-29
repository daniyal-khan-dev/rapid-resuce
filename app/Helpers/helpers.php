<?php

use App\Models\Admin\Log;
use App\Events\LogHistoryCreated;

function logHistory($username, $ipAddress, $action)
{
    $log = Log::create([
        'username'   => $username,
        'ip_address' => $ipAddress,
        'action'     => $action,
    ]);

    event(new LogHistoryCreated(
        username:   $log->username,
        action:     $log->action,
        created_at: ($log->created_at ?? now())->toISOString(),
        ip_address: $log->ip_address ?? '',
    ));
}
