<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int     $id,
        public readonly string  $username,
        public readonly string  $status,
        public readonly string  $name,
        public readonly string  $email,
        public readonly ?string $phone,
        public readonly string  $profile_picture,
        public readonly ?string $created_at,
        public readonly bool    $verified,
        public readonly string  $action,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'user-changed';
    }
}