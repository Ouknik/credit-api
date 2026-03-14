<?php

namespace App\Services;

use App\Models\Recharge;
use App\Models\Debt;
use App\Models\RechargeTransaction;
use App\Repositories\ShopRepository;
use App\Repositories\RechargeRepository;
use App\Repositories\CustomerRepository;
use App\Models\AuditLog;
use App\Events\RechargeUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RechargeService
{
    public function __construct(
        private ShopRepository $shopRepository,
        private RechargeRepository $rechargeRepository,
        private CustomerRepository $customerRepository,
        private WalletService $walletService,
        private CadeauxGateway $gateway,
    ) {}

    public function getRechargesByShop(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->rechargeRepository->paginateByShopId($shopId, $perPage, $filters);
    }

    public function initiateRecharge(string $shopId, array $data): array
    {
        // Check for idempotency
        if (!empty($data['idempotency_key'])) {
            $existing = $this->rechargeRepository->findByIdempotencyKey($data['idempotency_key']);
            if ($existing) {
                return [
                    'success' => true,
                    'recharge' => $existing,
                    'message' => 'Recharge already processed',
                    'duplicate' => true,
                ];
            }
        }

        // Step 1: Create recharge + deduct balance (atomic)
        $recharge = DB::transaction(function () use ($shopId, $data) {
            $shop = $this->shopRepository->lockForUpdate($shopId);

            if (!$shop) {
                return ['error' => 'Shop not found'];
            }

            if (!$shop->isActive()) {
                return ['error' => 'Shop account is suspended'];
            }

            if (!$shop->hasEnoughBalance($data['amount'])) {
                return ['error' => 'Insufficient balance'];
            }

            $referenceCode = $this->generateReferenceCode();

            $recharge = $this->rechargeRepository->create([
                'shop_id' => $shopId,
                'customer_id' => $data['customer_id'] ?? null,
                'phone' => $data['phone'],
                'operator' => $data['operator'],
                'amount' => $data['amount'],
                'offer' => $data['offer'] ?? null,
                'as_debt' => !empty($data['as_debt']),
                'status' => 'pending',
                'reference_code' => $referenceCode,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            $this->walletService->deduct(
                shopId: $shopId,
                amount: $data['amount'],
                type: 'recharge',
                description: "Recharge {$data['phone']} ({$data['operator']})",
                reference: $referenceCode,
            );

            AuditLog::log($shopId, 'recharge.initiated', [
                'recharge_id' => $recharge->id,
                'reference_code' => $referenceCode,
                'phone' => $data['phone'],
                'operator' => $data['operator'],
                'amount' => $data['amount'],
                'balance_after' => $shop->balance,
            ]);

            return $recharge;
        });

        // Transaction returned an error array
        if (is_array($recharge) && isset($recharge['error'])) {
            return ['success' => false, 'message' => $recharge['error']];
        }

        // Step 2: Send to Pi DIRECTLY (outside transaction — no DB lock held)
        try {
            $response = $this->gateway->sendRecharge(
                orderId: $recharge->reference_code,
                phone: $recharge->phone,
                price: (float) $recharge->amount,
                offer: $recharge->offer ?? '0',
            );

            $recharge->update(['gateway_response' => $response]);

            Log::info('RechargeService: delivered to gateway', [
                'recharge_id' => $recharge->id,
                'queue_pos'   => $response['queue'] ?? null,
                'status'      => $response['status'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'recharge' => $recharge,
                'message' => 'Recharge initiated successfully',
            ];

        } catch (\Exception $e) {
            // Pi unreachable — refund immediately
            Log::error('RechargeService: gateway unreachable, refunding', [
                'recharge_id' => $recharge->id,
                'error'       => $e->getMessage(),
            ]);

            $this->handleRechargeFailure($recharge, [
                'success' => false,
                'error'   => 'Gateway unreachable: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'recharge' => $recharge->refresh(),
                'message' => 'Gateway unreachable, balance refunded',
            ];
        }
    }

    public function handleRechargeSuccess(Recharge $recharge, array $response): void
    {
        $recharge->refresh();
        if ($recharge->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($recharge, $response) {
            $recharge->markAsSuccess();

            RechargeTransaction::create([
                'recharge_id' => $recharge->id,
                'raw_response' => $response,
                'processed_at' => now(),
            ]);

            // If customer exists AND as_debt is true, create debt record
            if ($recharge->customer_id && $recharge->as_debt) {
                Debt::create([
                    'shop_id' => $recharge->shop_id,
                    'customer_id' => $recharge->customer_id,
                    'amount' => $recharge->amount,
                    'type' => 'recharge',
                    'description' => "Recharge {$recharge->phone} - {$recharge->operator}",
                ]);
            }

            AuditLog::log($recharge->shop_id, 'recharge.success', [
                'recharge_id' => $recharge->id,
                'reference_code' => $recharge->reference_code,
            ]);
        });

        $this->broadcastRechargeUpdate($recharge);
    }

    public function handleRechargeFailure(Recharge $recharge, array $response): void
    {
        $recharge->refresh();
        if ($recharge->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($recharge, $response) {
            $recharge->markAsFailed();

            RechargeTransaction::create([
                'recharge_id' => $recharge->id,
                'raw_response' => $response,
                'processed_at' => now(),
            ]);

            // Refund balance via WalletService (atomic, creates WalletTransaction)
            $this->walletService->refund(
                shopId: $recharge->shop_id,
                amount: $recharge->amount,
                description: "Remboursement recharge échouée {$recharge->phone}",
                reference: $recharge->reference_code,
            );

            AuditLog::log($recharge->shop_id, 'recharge.failed', [
                'recharge_id' => $recharge->id,
                'reference_code' => $recharge->reference_code,
                'refunded_amount' => $recharge->amount,
            ]);
        });

        $this->broadcastRechargeUpdate($recharge);
    }

    public function getRechargeStats(string $shopId): array
    {
        return [
            'today_count' => $this->rechargeRepository->getTodayRechargesCountByShopId($shopId),
            'today_amount' => $this->rechargeRepository->getTodayRechargesAmountByShopId($shopId),
        ];
    }

    private function generateReferenceCode(): string
    {
        do {
            $code = 'RCH-' . strtoupper(Str::random(12));
        } while ($this->rechargeRepository->findByReferenceCode($code));

        return $code;
    }

    public function handleRechargeRejected(Recharge $recharge, array $response): void
    {
        $recharge->refresh();
        if ($recharge->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($recharge, $response) {
            $recharge->markAsRejected();

            RechargeTransaction::create([
                'recharge_id' => $recharge->id,
                'raw_response' => $response,
                'processed_at' => now(),
            ]);

            // Refund balance
            $this->walletService->refund(
                shopId: $recharge->shop_id,
                amount: $recharge->amount,
                description: "Remboursement recharge rejetée {$recharge->phone}",
                reference: $recharge->reference_code,
            );

            AuditLog::log($recharge->shop_id, 'recharge.rejected', [
                'recharge_id' => $recharge->id,
                'reference_code' => $recharge->reference_code,
                'refunded_amount' => $recharge->amount,
            ]);
        });

        $this->broadcastRechargeUpdate($recharge);
    }

    private function broadcastRechargeUpdate(Recharge $recharge): void
    {
        try {
            event(new RechargeUpdated(
                shopId: $recharge->shop_id,
                rechargeId: $recharge->id,
                referenceCode: $recharge->reference_code,
                status: $recharge->status,
                phone: $recharge->phone,
                amount: (float) $recharge->amount,
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to broadcast recharge update', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
