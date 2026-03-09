<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Shop;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
    }

    // ═══════════════════════════════════════════════
    //  DEPOSIT TESTS
    // ═══════════════════════════════════════════════

    public function test_deposit_increases_balance(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $tx = $this->walletService->deposit(
            shopId: $shop->id,
            amount: 500,
            description: 'Test deposit',
            adminId: $admin->id,
        );

        $shop->refresh();

        $this->assertEquals(1500.00, (float) $shop->balance);
        $this->assertEquals('deposit', $tx->type);
        $this->assertEquals(500, $tx->amount);
        $this->assertEquals(1000, $tx->balance_before);
        $this->assertEquals(1500, $tx->balance_after);
        $this->assertEquals($admin->id, $tx->created_by);
        $this->assertStringStartsWith('DEP-', $tx->reference);
    }

    public function test_deposit_creates_audit_log(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();

        $tx = $this->walletService->deposit($shop->id, 1000);

        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'action' => 'wallet.deposit',
        ]);
    }

    public function test_deposit_rejects_amount_below_minimum(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('100 MAD');

        $this->walletService->deposit($shop->id, 50);
    }

    public function test_deposit_rejects_zero_amount(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->deposit($shop->id, 0);
    }

    public function test_deposit_rejects_negative_amount(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->deposit($shop->id, -500);
    }

    public function test_deposit_rejects_suspended_shop(): void
    {
        $shop = Shop::factory()->suspended()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('suspendue');

        $this->walletService->deposit($shop->id, 500);
    }

    // ═══════════════════════════════════════════════
    //  DEDUCT TESTS
    // ═══════════════════════════════════════════════

    public function test_deduct_decreases_balance(): void
    {
        $shop = Shop::factory()->withBalance(2000)->create();

        $tx = $this->walletService->deduct(
            shopId: $shop->id,
            amount: 500,
            type: 'recharge',
            description: 'Recharge test',
            reference: 'RCH-TEST123',
        );

        $shop->refresh();

        $this->assertEquals(1500.00, (float) $shop->balance);
        $this->assertEquals('recharge', $tx->type);
        $this->assertEquals(500, $tx->amount);
        $this->assertEquals(2000, $tx->balance_before);
        $this->assertEquals(1500, $tx->balance_after);
    }

    public function test_deduct_fails_on_insufficient_balance(): void
    {
        $shop = Shop::factory()->withBalance(100)->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('insuffisant');

        $this->walletService->deduct($shop->id, 500, 'recharge');
    }

    public function test_deduct_fails_on_exact_zero(): void
    {
        $shop = Shop::factory()->withBalance(500)->create();

        // Should succeed — deduct exactly the balance
        $tx = $this->walletService->deduct($shop->id, 500, 'recharge');
        $shop->refresh();

        $this->assertEquals(0.00, (float) $shop->balance);
        $this->assertEquals(0, $tx->balance_after);
    }

    public function test_deduct_rejects_invalid_type(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->deduct($shop->id, 100, 'invalid_type');
    }

    // ═══════════════════════════════════════════════
    //  REFUND TESTS
    // ═══════════════════════════════════════════════

    public function test_refund_increases_balance(): void
    {
        $shop = Shop::factory()->withBalance(500)->create();

        $tx = $this->walletService->refund(
            shopId: $shop->id,
            amount: 200,
            description: 'Remboursement test',
            reference: 'RCH-REF123',
        );

        $shop->refresh();

        $this->assertEquals(700.00, (float) $shop->balance);
        $this->assertEquals('refund', $tx->type);
        $this->assertEquals(200, $tx->amount);
        $this->assertEquals(500, $tx->balance_before);
        $this->assertEquals(700, $tx->balance_after);
    }

    public function test_refund_creates_audit_log(): void
    {
        $shop = Shop::factory()->withBalance(100)->create();

        $this->walletService->refund($shop->id, 300);

        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'action' => 'wallet.refund',
        ]);
    }

    // ═══════════════════════════════════════════════
    //  ADJUSTMENT TESTS
    // ═══════════════════════════════════════════════

    public function test_positive_adjustment_adds_balance(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $tx = $this->walletService->adjustment(
            shopId: $shop->id,
            amount: 250,
            description: 'Correction positive',
            adminId: $admin->id,
        );

        $shop->refresh();

        $this->assertEquals(1250.00, (float) $shop->balance);
        $this->assertEquals('adjustment', $tx->type);
        $this->assertEquals(250, $tx->amount);
        $this->assertEquals(1250, $tx->balance_after);
    }

    public function test_negative_adjustment_reduces_balance(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();

        $tx = $this->walletService->adjustment($shop->id, -300, 'Correction négative');

        $shop->refresh();

        $this->assertEquals(700.00, (float) $shop->balance);
        $this->assertEquals(300, $tx->amount); // stored as absolute
        $this->assertEquals(700, $tx->balance_after);
    }

    public function test_negative_adjustment_prevents_negative_balance(): void
    {
        $shop = Shop::factory()->withBalance(100)->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('négatif');

        $this->walletService->adjustment($shop->id, -200);
    }

    public function test_adjustment_rejects_zero(): void
    {
        $shop = Shop::factory()->withBalance(1000)->create();

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->adjustment($shop->id, 0);
    }

    // ═══════════════════════════════════════════════
    //  SUMMARY / HISTORY TESTS
    // ═══════════════════════════════════════════════

    public function test_get_shop_summary_returns_correct_totals(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();

        $this->walletService->deposit($shop->id, 5000);
        $this->walletService->deduct($shop->id, 1000, 'recharge');
        $this->walletService->refund($shop->id, 200);
        $this->walletService->adjustment($shop->id, 100);

        $summary = $this->walletService->getShopSummary($shop->id);

        $this->assertEquals(4300.00, $summary['current_balance']);
        $this->assertEquals(5000.00, $summary['total_deposits']);
        $this->assertEquals(1000.00, $summary['total_recharges']);
        $this->assertEquals(200.00, $summary['total_refunds']);
        $this->assertEquals(100.00, $summary['total_adjustments']);
    }

    public function test_get_shop_history_returns_paginated_results(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();

        $this->walletService->deposit($shop->id, 1000);
        $this->walletService->deposit($shop->id, 2000);

        $history = $this->walletService->getShopHistory($shop->id, perPage: 10);

        $this->assertCount(2, $history->items());
    }

    public function test_get_shop_history_filters_by_type(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();

        $this->walletService->deposit($shop->id, 5000);
        $this->walletService->deduct($shop->id, 1000, 'recharge');

        $depositHistory = $this->walletService->getShopHistory($shop->id, type: 'deposit');
        $rechargeHistory = $this->walletService->getShopHistory($shop->id, type: 'recharge');

        $this->assertCount(1, $depositHistory->items());
        $this->assertCount(1, $rechargeHistory->items());
    }

    public function test_global_summary_returns_all_shops_data(): void
    {
        $shop1 = Shop::factory()->withBalance(0)->create();
        $shop2 = Shop::factory()->withBalance(0)->create();

        $this->walletService->deposit($shop1->id, 3000);
        $this->walletService->deposit($shop2->id, 2000);

        $summary = $this->walletService->getGlobalSummary();

        $this->assertEquals(5000.00, $summary['total_deposits']);
        $this->assertEquals(5000.00, $summary['total_balance']);
        $this->assertEquals(2, $summary['transactions_count']);
    }

    // ═══════════════════════════════════════════════
    //  WALLET TRANSACTION MODEL TESTS
    // ═══════════════════════════════════════════════

    public function test_wallet_transaction_has_correct_relationships(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $tx = $this->walletService->deposit($shop->id, 1000, adminId: $admin->id);

        $this->assertEquals($shop->id, $tx->shop->id);
        $this->assertEquals($admin->id, $tx->admin->id);
    }

    public function test_wallet_transaction_credit_debit_helpers(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();

        $depositTx = $this->walletService->deposit($shop->id, 5000);
        $rechargeTx = $this->walletService->deduct($shop->id, 1000, 'recharge');
        $refundTx = $this->walletService->refund($shop->id, 200);

        $this->assertTrue($depositTx->isCredit());
        $this->assertFalse($depositTx->isDebit());

        $this->assertTrue($rechargeTx->isDebit());
        $this->assertFalse($rechargeTx->isCredit());

        $this->assertTrue($refundTx->isCredit());
    }

    // ═══════════════════════════════════════════════
    //  ATOMICITY / INTEGRITY
    // ═══════════════════════════════════════════════

    public function test_failed_deduction_does_not_change_balance(): void
    {
        $shop = Shop::factory()->withBalance(100)->create();
        $originalBalance = (float) $shop->balance;

        try {
            $this->walletService->deduct($shop->id, 500, 'recharge');
        } catch (RuntimeException) {
            // expected
        }

        $shop->refresh();
        $this->assertEquals($originalBalance, (float) $shop->balance);
        $this->assertEquals(0, WalletTransaction::forShop($shop->id)->count());
    }

    public function test_balance_trail_is_consistent(): void
    {
        $shop = Shop::factory()->withBalance(0)->create();

        $this->walletService->deposit($shop->id, 10000);
        $this->walletService->deduct($shop->id, 3000, 'recharge');
        $this->walletService->deduct($shop->id, 2000, 'recharge');
        $this->walletService->refund($shop->id, 500);
        $this->walletService->adjustment($shop->id, -200);

        $shop->refresh();

        // 0 + 10000 - 3000 - 2000 + 500 - 200 = 5300
        $this->assertEquals(5300.00, (float) $shop->balance);

        // Verify last transaction's balance_after matches current balance
        $lastTx = WalletTransaction::forShop($shop->id)->latest()->first();
        $this->assertEquals((float) $shop->balance, (float) $lastTx->balance_after);

        // Verify transaction count
        $this->assertEquals(5, WalletTransaction::forShop($shop->id)->count());
    }
}
