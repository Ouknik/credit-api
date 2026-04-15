<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ProcurementRealtimeUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public string $shopId,
        public string $eventType,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("shop.{$this->shopId}")];
    }

    public function broadcastAs(): string
    {
        return 'procurement.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event_type' => $this->eventType,
            'occurred_at' => now()->toIso8601String(),
            ...$this->payload,
        ];
    }
}
