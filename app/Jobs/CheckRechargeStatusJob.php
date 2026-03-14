<?php

namespace App\Jobs;

use App\Models\Recharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * DEPRECATED — No longer used.
 *
 * The Raspberry Pi gateway now handles the full recharge lifecycle:
 * execute → wait for SMS confirmation → callback to Laravel.
 *
 * Laravel no longer polls the gateway. This class is kept only to
 * prevent errors if old queued jobs still reference it.
 */
class CheckRechargeStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public Recharge $recharge,
        public int $attempt = 1,
    ) {
        $this->onQueue('recharges');
    }

    /**
     * Any remaining queued instances simply do nothing and exit.
     */
    public function handle(): void
    {
        // No-op: the callback-based flow replaced polling.
    }
}
