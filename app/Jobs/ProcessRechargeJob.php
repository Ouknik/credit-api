<?php

namespace App\Jobs;

use App\Models\Recharge;
use App\Services\CadeauxGateway;
use App\Services\RechargeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send the recharge request to the Raspberry Pi gateway ONCE.
 *
 * The Pi is the queue manager: it executes recharges one at a time,
 * waits for the SMS confirmation, and sends back a callback.
 *
 * Laravel MUST NOT retry — the Pi handles all retry/confirmation logic.
 */
class ProcessRechargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;      // Send ONCE — no automatic retry
    public int $timeout = 30;   // Only needs to deliver to Pi, not wait for result

    public function __construct(
        public Recharge $recharge
    ) {
        $this->onQueue('recharges');
    }

    public function handle(CadeauxGateway $gateway): void
    {
        $this->recharge->refresh();

        // Guard: already in terminal state — nothing to do
        if ($this->recharge->isTerminal()) {
            return;
        }

        // Guard: already sent to gateway — do NOT re-send
        if ($this->recharge->gateway_response !== null) {
            Log::info('ProcessRechargeJob: already sent to gateway, skipping', [
                'recharge_id' => $this->recharge->id,
            ]);
            return;
        }

        Log::info('ProcessRechargeJob: sending to gateway', [
            'recharge_id'    => $this->recharge->id,
            'reference_code' => $this->recharge->reference_code,
            'phone'          => $this->recharge->phone,
            'operator'       => $this->recharge->operator,
            'amount'         => $this->recharge->amount,
            'offer'          => $this->recharge->offer,
        ]);

        // Send recharge request to Raspberry Pi (fire and forget)
        // The Pi will queue it locally, execute it, and send callback when done
        $response = $gateway->sendRecharge(
            orderId: $this->recharge->reference_code,
            phone: $this->recharge->phone,
            price: (float) $this->recharge->amount,
            offer: $this->recharge->offer ?? '0',
        );

        // Save gateway acknowledgement
        $this->recharge->update([
            'gateway_response' => $response,
        ]);

        Log::info('ProcessRechargeJob: delivered to gateway', [
            'recharge_id' => $this->recharge->id,
            'queue_pos'   => $response['queue'] ?? null,
            'status'      => $response['status'] ?? 'unknown',
        ]);
    }

    /**
     * Called if delivering to the Pi fails (network error, Pi down).
     * Refund the shop balance since we could not reach the gateway.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRechargeJob: failed to deliver to gateway', [
            'recharge_id' => $this->recharge->id,
            'error'       => $exception->getMessage(),
        ]);

        $rechargeService = app(RechargeService::class);
        $rechargeService->handleRechargeFailure($this->recharge, [
            'success' => false,
            'error'   => 'Gateway unreachable: ' . $exception->getMessage(),
        ]);
    }
}
