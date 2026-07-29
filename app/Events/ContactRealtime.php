<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactRealtime implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $event,
        public array $data,
        public array $channels
    ) {}

    public function broadcastOn(): array
    {
        return array_map(function ($channel) {
            if (str_starts_with($channel, 'private:')) {
                return new PrivateChannel(substr($channel, 8));
            }

            return new Channel($channel);
        }, $this->channels);
    }

    public function broadcastAs(): string
    {
        return $this->event;
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}