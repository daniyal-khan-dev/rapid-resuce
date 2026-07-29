<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $entity,
        public readonly string $action,
        public readonly array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('drivers-update')];
    }

    public function broadcastAs(): string
    {
        return 'drivers-update';
    }
}