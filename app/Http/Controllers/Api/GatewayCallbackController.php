<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Services\RechargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives callback from the Raspberry Pi gateway when a recharge finishes.
 * This replaces the polling mechanism — the Pi pushes results directly.
 */
class GatewayCallbackController extends Controller
{
    public function __construct(
        private RechargeService $rechargeService
    ) {}

    /**
     * POST /api/gateway/callback
     *
     * Body: { "order_id": "RCH-XXXX", "status": "success|failed|rejected|balance_error", "message": "raw SMS text" }
     * Header: token: <GATEWAY_TOKEN>
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Authenticate by gateway token
        $token = $request->header('token');
        $expectedToken = config('services.cadeaux.token');

        if (!$token || !hash_equals($expectedToken, $token)) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $orderId = $request->input('order_id');
        $status  = $request->input('status');
        $message = $request->input('message', '');

        if (!$orderId || !$status) {
            return response()->json(['error' => 'missing order_id or status'], 422);
        }

        Log::info('GatewayCallback: received', compact('orderId', 'status', 'message'));

        // Find recharge by reference_code (= order_id sent to Pi)
        $recharge = Recharge::where('reference_code', $orderId)->first();

        if (!$recharge) {
            Log::warning('GatewayCallback: recharge not found', ['order_id' => $orderId]);
            return response()->json(['error' => 'recharge not found'], 404);
        }

        // Skip if already in terminal state
        if ($recharge->isTerminal()) {
            Log::info('GatewayCallback: already terminal', [
                'order_id' => $orderId,
                'status'   => $recharge->status,
            ]);
            return response()->json(['status' => 'already_processed']);
        }

        // Save the raw SMS message
        $recharge->update(['gateway_message' => $message]);

        // Handle based on status
        $response = ['order_id' => $orderId, 'status' => $status];

        switch ($status) {
            case 'success':
                $this->rechargeService->handleRechargeSuccess($recharge, [
                    'success'    => true,
                    'message'    => $message,
                    'source'     => 'gateway_callback',
                ]);
                break;

            case 'balance_error':
                $this->rechargeService->handleRechargeFailure($recharge, [
                    'success' => false,
                    'error'   => 'Gateway SIM balance insufficient',
                    'message' => $message,
                    'source'  => 'gateway_callback',
                ]);
                break;

            case 'rejected':
                $this->rechargeService->handleRechargeRejected($recharge, [
                    'success' => false,
                    'error'   => 'Recharge rejected by operator',
                    'message' => $message,
                    'source'  => 'gateway_callback',
                ]);
                break;

            case 'no_signal':
                // Non-terminal: just update status + save message, don't refund
                $recharge->update(['status' => 'no_signal']);
                Log::info('GatewayCallback: no_signal, waiting for retry', ['order_id' => $orderId]);
                $this->broadcastUpdate($recharge);
                break;

            case 'queued':
                // Non-terminal: just update status + save message
                $recharge->update(['status' => 'queued']);
                Log::info('GatewayCallback: queued on gateway', ['order_id' => $orderId]);
                $this->broadcastUpdate($recharge);
                break;

            case 'processing':
                $recharge->markAsProcessing();
                Log::info('GatewayCallback: processing', ['order_id' => $orderId]);
                $this->broadcastUpdate($recharge);
                break;

            case 'failed':
            default:
                $this->rechargeService->handleRechargeFailure($recharge, [
                    'success' => false,
                    'error'   => 'Recharge failed',
                    'message' => $message,
                    'source'  => 'gateway_callback',
                ]);
                break;
        }

        Log::info('GatewayCallback: processed', compact('orderId', 'status'));

        return response()->json(['status' => 'ok', 'processed' => $status]);
    }

    /**
     * Broadcast a non-terminal status update via Pusher.
     */
    private function broadcastUpdate(Recharge $recharge): void
    {
        try {
            event(new \App\Events\RechargeUpdated(
                shopId: $recharge->shop_id,
                rechargeId: $recharge->id,
                referenceCode: $recharge->reference_code,
                status: $recharge->status,
                phone: $recharge->phone,
                amount: (float) $recharge->amount,
            ));
        } catch (\Exception $e) {
            Log::warning('GatewayCallback: broadcast failed', ['error' => $e->getMessage()]);
        }
    }
}
