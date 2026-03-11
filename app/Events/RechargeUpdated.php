<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class RechargeUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public string $shopId,
        public string $rechargeId,
        public string $referenceCode,
        public string $status,
        public string $phone,
        public float $amount,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("shop.{$this->shopId}")];
    }

    public function broadcastAs(): string
    {
        return 'recharge.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'recharge_id' => $this->rechargeId,
            'reference_code' => $this->referenceCode,
            'status' => $this->status,
            'phone' => $this->phone,
            'amount' => $this->amount,
        ];
    }
}
