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
 * Step 1: Send the recharge request to the Raspberry Pi gateway.
 * After queuing, dispatch CheckRechargeStatusJob to poll for result.
 */
class ProcessRechargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;
    public int $timeout = 60;

    public function __construct(
        public Recharge $recharge
    ) {
        $this->onQueue('recharges');
    }

    public function handle(CadeauxGateway $gateway, RechargeService $rechargeService): void
    {
        // Guard: if already sent to gateway (retry safety), skip re-send
        $this->recharge->refresh();
        if ($this->recharge->isTerminal()) {
            return;
        }

        if ($this->recharge->gateway_response !== null) {
            // Already sent to Pi — just ensure status checker is running
            CheckRechargeStatusJob::dispatch($this->recharge)
                ->delay(now()->addSeconds(10));
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

        try {
            // Send recharge request to Raspberry Pi
            $response = $gateway->sendRecharge(
                orderId: $this->recharge->reference_code,
                phone: $this->recharge->phone,
                price: (float) $this->recharge->amount,
                offer: $this->recharge->offer ?? '0',
            );

            // Save gateway response
            $this->recharge->update([
                'gateway_response' => $response,
            ]);

            Log::info('ProcessRechargeJob: queued on gateway', [
                'recharge_id' => $this->recharge->id,
                'queue_pos'   => $response['queue'] ?? null,
                'status'      => $response['status'] ?? 'unknown',
            ]);

            // Dispatch status checker — starts polling after 10 seconds
            CheckRechargeStatusJob::dispatch($this->recharge)
                ->delay(now()->addSeconds(10));

        } catch (\Exception $e) {
            Log::error('ProcessRechargeJob: gateway error', [
                'recharge_id' => $this->recharge->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRechargeJob: failed after all retries', [
            'recharge_id' => $this->recharge->id,
            'error'       => $exception->getMessage(),
        ]);

        // Refund balance after all retries exhausted
        $rechargeService = app(RechargeService::class);
        $rechargeService->handleRechargeFailure($this->recharge, [
            'success' => false,
            'error'   => 'Gateway unreachable after maximum retries: ' . $exception->getMessage(),
        ]);
    }
}
