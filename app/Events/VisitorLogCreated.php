<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorLogCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string  $ip_address,
        public readonly ?string $browser,
        public readonly ?string $platform,
        public readonly ?string $device,
        public readonly bool    $is_mobile,
        public readonly string  $created_at,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'visitor-log-created';
    }
}
