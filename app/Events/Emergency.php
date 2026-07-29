<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Emergency implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected array $channels;
    protected string $eventName;
    public array $payload;

    /**
     * @param array|string $channels
     * @param string $eventName
     * @param array $payload
     */
    public function __construct(array|string $channels, string $eventName, array $payload = [])
    {
        $this->channels = is_array($channels) ? $channels : [$channels];
        $this->eventName = $eventName;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        $result = [];

        foreach ($this->channels as $channel) {

            if (str_starts_with($channel, 'private:')) {

                $result[] = new PrivateChannel(
                    substr($channel, 8)
                );

            } elseif (str_starts_with($channel, 'presence:')) {

                $result[] = new PresenceChannel(
                    substr($channel, 9)
                );

            } else {

                $result[] = new Channel($channel);

            }
        }

        return $result;
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}