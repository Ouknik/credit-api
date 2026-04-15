<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Recharge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        // Create demo shop
        $shop = Shop::firstOrCreate(
            ['email' => 'demo@shop.com'],
            [
                'name' => 'Demo Shop',
                'password' => bcrypt('password123'),
                'balance' => 1000.00,
                'status' => 'active',
                'role' => Shop::ROLE_SHOP_OWNER,
            ]
        );

        $distributor = Shop::firstOrCreate(
            ['email' => 'demo-distributor@shop.com'],
            [
                'name' => 'Demo Distributor',
                'password' => bcrypt('password123'),
                'balance' => 5000.00,
                'status' => 'active',
                'role' => Shop::ROLE_DISTRIBUTOR,
            ]
        );

        $this->command->info("Demo shop created: demo@shop.com / password123");
        $this->command->info("Demo distributor created: demo-distributor@shop.com / password123");

        // Create customers for the demo shop
        $customers = Customer::factory(10)->create([
            'shop_id' => $shop->id,
        ]);

        $this->command->info("Created 10 customers");

        // Create some debts for random customers
        foreach ($customers->random(5) as $customer) {
            $debtCount = rand(1, 3);
            for ($i = 0; $i < $debtCount; $i++) {
                Debt::factory()->create([
                    'shop_id' => $shop->id,
                    'customer_id' => $customer->id,
                ]);
            }
            
            // Add some payments
            if (rand(0, 1)) {
                Debt::factory()->payment()->create([
                    'shop_id' => $shop->id,
                    'customer_id' => $customer->id,
                    'amount' => rand(20, 100),
                ]);
            }
            
            $customer->updateTotalDebt();
        }

        $this->command->info("Created debts and payments");

        // Create some recharges
        foreach ($customers->random(7) as $customer) {
            $rechargeCount = rand(1, 5);
            for ($i = 0; $i < $rechargeCount; $i++) {
                Recharge::factory()->create([
                    'shop_id' => $shop->id,
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        }

        $this->command->info("Created recharges");

        // Create additional shops for testing
        Shop::factory(2)->shopOwner()->create();
        Shop::factory()->distributor()->create();
        
        $this->command->info("Created 2 additional test shops");
        $this->command->info("Seeding completed successfully!");
    }
}
