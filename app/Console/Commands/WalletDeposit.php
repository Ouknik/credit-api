<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\WalletService;
use Illuminate\Console\Command;

class WalletDeposit extends Command
{
    protected $signature = 'wallet:deposit {email} {amount}';
    protected $description = 'Deposit money into a shop wallet';

    public function handle(WalletService $walletService): int
    {
        $email  = $this->argument('email');
        $amount = (float) $this->argument('amount');

        $shop = Shop::where('email', $email)->first();

        if (! $shop) {
            $this->error("Shop with email [{$email}] not found.");
            return 1;
        }

        if ($amount < 100) {
            $this->error('Minimum deposit is 100 MAD.');
            return 1;
        }

        $tx = $walletService->deposit(
            shopId: $shop->id,
            amount: $amount,
            description: "CLI deposit via artisan",
        );

        $this->info("✅ Deposited {$amount} MAD to [{$shop->name}] ({$email})");
        $this->info("   Balance: {$tx->balance_before} → {$tx->balance_after} MAD");

        return 0;
    }
}
