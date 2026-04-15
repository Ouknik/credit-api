<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ProcurementMarketUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public string $eventType,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('market.distributors')];
    }

    public function broadcastAs(): string
    {
        return 'procurement.market.updated';
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
