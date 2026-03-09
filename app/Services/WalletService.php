<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Central service for ALL wallet/balance operations.
 *
 * RULE: No code outside this service may modify Shop::balance directly.
 * Every balance change creates a WalletTransaction record atomically.
 */
class WalletService
{
    // ════════════════════════════════════════════════
    //  DEPOSIT  (Admin adds money)
    // ════════════════════════════════════════════════

    /**
     * @param  string       $shopId
     * @param  float        $amount   Must be >= 100
     * @param  string|null  $description
     * @param  int|null     $adminId  The admin user doing the deposit
     * @return WalletTransaction
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function deposit(
        string $shopId,
        float $amount,
        ?string $description = null,
        ?int $adminId = null,
    ): WalletTransaction {
        $this->validatePositiveAmount($amount);

        if ($amount < 100) {
            throw new InvalidArgumentException('Le montant minimum de dépôt est 100 MAD.');
        }

        return DB::transaction(function () use ($shopId, $amount, $description, $adminId) {
            $shop = $this->lockShop($shopId);

            $balanceBefore = (float) $shop->balance;
            $balanceAfter  = $balanceBefore + $amount;

            // Update balance
            $shop->balance = $balanceAfter;
            $shop->save();

            // Record transaction
            $tx = WalletTransaction::create([
                'shop_id'        => $shopId,
                'type'           => 'deposit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => $description ?? 'Dépôt manuel',
                'reference'      => $this->generateReference('DEP'),
                'created_by'     => $adminId,
            ]);

            // Audit
            AuditLog::log($shopId, 'wallet.deposit', [
                'wallet_transaction_id' => $tx->id,
                'amount'                => $amount,
                'balance_before'        => $balanceBefore,
                'balance_after'         => $balanceAfter,
                'admin_id'              => $adminId,
                'description'           => $description,
            ]);

            return $tx;
        });
    }

    // ════════════════════════════════════════════════
    //  DEDUCT  (Recharge uses this)
    // ════════════════════════════════════════════════

    /**
     * Deduct balance for a recharge or other operation.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function deduct(
        string $shopId,
        float $amount,
        string $type = 'recharge',
        ?string $description = null,
        ?string $reference = null,
    ): WalletTransaction {
        $this->validatePositiveAmount($amount);

        if (!in_array($type, ['recharge', 'adjustment'])) {
            throw new InvalidArgumentException("Invalid deduction type: {$type}");
        }

        return DB::transaction(function () use ($shopId, $amount, $type, $description, $reference) {
            $shop = $this->lockShop($shopId);

            $balanceBefore = (float) $shop->balance;

            if ($balanceBefore < $amount) {
                throw new RuntimeException('Solde insuffisant.');
            }

            $balanceAfter = $balanceBefore - $amount;

            $shop->balance = $balanceAfter;
            $shop->save();

            return WalletTransaction::create([
                'shop_id'        => $shopId,
                'type'           => $type,
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => $description,
                'reference'      => $reference,
                'created_by'     => null,
            ]);
        });
    }

    // ════════════════════════════════════════════════
    //  REFUND  (Failed recharge refund)
    // ════════════════════════════════════════════════

    /**
     * Refund a previously deducted amount.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function refund(
        string $shopId,
        float $amount,
        ?string $description = null,
        ?string $reference = null,
    ): WalletTransaction {
        $this->validatePositiveAmount($amount);

        return DB::transaction(function () use ($shopId, $amount, $description, $reference) {
            $shop = $this->lockShop($shopId);

            $balanceBefore = (float) $shop->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $shop->balance = $balanceAfter;
            $shop->save();

            $tx = WalletTransaction::create([
                'shop_id'        => $shopId,
                'type'           => 'refund',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => $description ?? 'Remboursement',
                'reference'      => $reference,
                'created_by'     => null,
            ]);

            AuditLog::log($shopId, 'wallet.refund', [
                'wallet_transaction_id' => $tx->id,
                'amount'                => $amount,
                'balance_before'        => $balanceBefore,
                'balance_after'         => $balanceAfter,
                'reference'             => $reference,
            ]);

            return $tx;
        });
    }

    // ════════════════════════════════════════════════
    //  ADJUSTMENT  (Admin correction)
    // ════════════════════════════════════════════════

    /**
     * Admin adjustment — can be positive (credit) or negative (debit).
     *
     * @param  float  $amount  Positive = add, Negative = deduct
     * @throws RuntimeException
     */
    public function adjustment(
        string $shopId,
        float $amount,
        ?string $description = null,
        ?int $adminId = null,
    ): WalletTransaction {
        if ($amount == 0) {
            throw new InvalidArgumentException('Le montant ne peut pas être zéro.');
        }

        return DB::transaction(function () use ($shopId, $amount, $description, $adminId) {
            $shop = $this->lockShop($shopId);

            $balanceBefore = (float) $shop->balance;
            $balanceAfter  = $balanceBefore + $amount;

            if ($balanceAfter < 0) {
                throw new RuntimeException('L\'ajustement rendrait le solde négatif.');
            }

            $shop->balance = $balanceAfter;
            $shop->save();

            $tx = WalletTransaction::create([
                'shop_id'        => $shopId,
                'type'           => 'adjustment',
                'amount'         => abs($amount),
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => $description ?? 'Ajustement admin',
                'reference'      => $this->generateReference('ADJ'),
                'created_by'     => $adminId,
            ]);

            AuditLog::log($shopId, 'wallet.adjustment', [
                'wallet_transaction_id' => $tx->id,
                'amount'                => $amount,
                'balance_before'        => $balanceBefore,
                'balance_after'         => $balanceAfter,
                'admin_id'              => $adminId,
                'description'           => $description,
            ]);

            return $tx;
        });
    }

    // ════════════════════════════════════════════════
    //  QUERY HELPERS
    // ════════════════════════════════════════════════

    /**
     * Get wallet summary across all shops (admin dashboard).
     */
    public function getGlobalSummary(): array
    {
        return [
            'total_deposits'   => (float) WalletTransaction::where('type', 'deposit')->sum('amount'),
            'total_recharges'  => (float) WalletTransaction::where('type', 'recharge')->sum('amount'),
            'total_refunds'    => (float) WalletTransaction::where('type', 'refund')->sum('amount'),
            'total_adjustments'=> (float) WalletTransaction::where('type', 'adjustment')->sum('amount'),
            'total_balance'    => (float) Shop::sum('balance'),
            'transactions_count' => WalletTransaction::count(),
        ];
    }

    /**
     * Get wallet summary for a specific shop.
     */
    public function getShopSummary(string $shopId): array
    {
        $shop = Shop::findOrFail($shopId);

        return [
            'current_balance'  => (float) $shop->balance,
            'total_deposits'   => (float) WalletTransaction::forShop($shopId)->ofType('deposit')->sum('amount'),
            'total_recharges'  => (float) WalletTransaction::forShop($shopId)->ofType('recharge')->sum('amount'),
            'total_refunds'    => (float) WalletTransaction::forShop($shopId)->ofType('refund')->sum('amount'),
            'total_adjustments'=> (float) WalletTransaction::forShop($shopId)->ofType('adjustment')->sum('amount'),
        ];
    }

    /**
     * Get paginated wallet history for a shop.
     */
    public function getShopHistory(string $shopId, int $perPage = 20, ?string $type = null)
    {
        $query = WalletTransaction::forShop($shopId)
            ->with('admin:id,name')
            ->latest();

        if ($type) {
            $query->ofType($type);
        }

        return $query->paginate($perPage);
    }

    // ════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════

    private function lockShop(string $shopId): Shop
    {
        $shop = Shop::lockForUpdate()->find($shopId);

        if (!$shop) {
            throw new RuntimeException('Boutique introuvable.');
        }

        if (!$shop->isActive()) {
            throw new RuntimeException('La boutique est suspendue.');
        }

        return $shop;
    }

    private function validatePositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
        }
    }

    private function generateReference(string $prefix): string
    {
        return $prefix . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 12));
    }
}
