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
 * Step 2: Poll the Raspberry Pi gateway for recharge status.
 * Re-dispatches itself every 10 seconds until a terminal status is reached.
 * Max 30 attempts = ~5 minutes timeout.
 */
class CheckRechargeStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // Each dispatch is a single attempt
    public int $timeout = 30;

    private const MAX_POLLS = 30;       // 30 × 10s = 5 min max
    private const POLL_INTERVAL = 10;   // seconds between polls

    public function __construct(
        public Recharge $recharge,
        public int $attempt = 1,
    ) {
        $this->onQueue('recharges');
    }

    public function handle(CadeauxGateway $gateway, RechargeService $rechargeService): void
    {
        Log::info('CheckRechargeStatusJob: polling', [
            'recharge_id'    => $this->recharge->id,
            'reference_code' => $this->recharge->reference_code,
            'attempt'        => $this->attempt,
        ]);

        // 1. Guard: if recharge already reached terminal status, stop polling
        $this->recharge->refresh();
        if ($this->recharge->isTerminal()) {
            Log::info('CheckRechargeStatusJob: already terminal, skipping', [
                'recharge_id' => $this->recharge->id,
                'status'      => $this->recharge->status,
            ]);
            return;
        }

        try {
            // 2. Call gateway /status/{order_id}
            $response = $gateway->checkStatus($this->recharge->reference_code);
            $status   = $response['status'] ?? 'unknown';

            Log::info('CheckRechargeStatusJob: gateway response', [
                'recharge_id' => $this->recharge->id,
                'status'      => $status,
                'attempt'     => $this->attempt,
            ]);

            // 3. Save gateway response
            $this->recharge->update(['gateway_response' => $response]);

            // 4. Handle status
            switch ($status) {
                case 'success':
                    $rechargeService->handleRechargeSuccess($this->recharge, [
                        'success'            => true,
                        'transaction_id'     => $response['transaction_id'] ?? $this->recharge->reference_code,
                        'operator_reference' => $response['operator_ref'] ?? null,
                        'processed_at'       => now()->toIso8601String(),
                    ]);
                    Log::info('CheckRechargeStatusJob: SUCCESS', ['recharge_id' => $this->recharge->id]);
                    break;

                case 'failed':
                    $rechargeService->handleRechargeFailure($this->recharge, [
                        'success' => false,
                        'error'   => $response['error'] ?? 'Recharge failed on gateway',
                    ]);
                    Log::warning('CheckRechargeStatusJob: FAILED', ['recharge_id' => $this->recharge->id]);
                    break;

                case 'balance_error':
                    $this->recharge->markAsBalanceError();
                    $rechargeService->handleRechargeFailure($this->recharge, [
                        'success' => false,
                        'error'   => 'Gateway SIM balance insufficient',
                    ]);
                    Log::warning('CheckRechargeStatusJob: BALANCE_ERROR', ['recharge_id' => $this->recharge->id]);
                    break;

                case 'rejected':
                    $rechargeService->handleRechargeRejected($this->recharge, [
                        'success' => false,
                        'error'   => $response['error'] ?? 'Recharge rejected by gateway',
                    ]);
                    Log::warning('CheckRechargeStatusJob: REJECTED', ['recharge_id' => $this->recharge->id]);
                    break;

                case 'queued':
                case 'processing':
                case 'no_signal':
                    // Non-terminal: update status and re-poll
                    if ($status === 'processing') {
                        $this->recharge->markAsProcessing();
                    }
                    if ($status === 'no_signal') {
                        Log::warning('CheckRechargeStatusJob: gateway has no signal', [
                            'recharge_id' => $this->recharge->id,
                        ]);
                    }
                    $this->scheduleNextPoll($rechargeService);
                    break;

                default:
                    Log::warning('CheckRechargeStatusJob: unknown status', [
                        'recharge_id' => $this->recharge->id,
                        'status'      => $status,
                    ]);
                    $this->scheduleNextPoll($rechargeService);
                    break;
            }

        } catch (\Exception $e) {
            Log::error('CheckRechargeStatusJob: error', [
                'recharge_id' => $this->recharge->id,
                'error'       => $e->getMessage(),
                'attempt'     => $this->attempt,
            ]);

            // Network error — retry polling (don't fail the recharge yet)
            $this->scheduleNextPoll($rechargeService);
        }
    }

    /**
     * Re-dispatch polling job or timeout after MAX_POLLS attempts.
     */
    private function scheduleNextPoll(RechargeService $rechargeService): void
    {
        if ($this->attempt >= self::MAX_POLLS) {
            Log::error('CheckRechargeStatusJob: max polls reached, timing out', [
                'recharge_id' => $this->recharge->id,
                'attempts'    => $this->attempt,
            ]);

            $rechargeService->handleRechargeFailure($this->recharge, [
                'success' => false,
                'error'   => 'Gateway status polling timed out after ' . $this->attempt . ' attempts',
            ]);
            return;
        }

        self::dispatch($this->recharge, $this->attempt + 1)
            ->delay(now()->addSeconds(self::POLL_INTERVAL));
    }
}
