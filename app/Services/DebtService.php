<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Customer;
use App\Repositories\DebtRepository;
use App\Repositories\CustomerRepository;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DebtService
{
    public function __construct(
        private DebtRepository $debtRepository,
        private CustomerRepository $customerRepository
    ) {}

    public function getDebtsByShop(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->debtRepository->paginateByShopId($shopId, $perPage, $filters);
    }

    public function getDebtsByCustomer(string $shopId, string $customerId): array
    {
        $customer = $this->customerRepository->getByShopIdAndId($shopId, $customerId);
        
        if (!$customer) {
            return [];
        }

        return $this->debtRepository->getByCustomerId($customerId)->toArray();
    }

    public function addDebt(string $shopId, string $customerId, array $data): ?Debt
    {
        $customer = $this->customerRepository->getByShopIdAndId($shopId, $customerId);
        
        if (!$customer) {
            return null;
        }

        // Check debt limit
        if (!$customer->canTakeDebt($data['amount'])) {
            throw new \Exception('Customer has reached maximum debt limit');
        }

        return DB::transaction(function () use ($shopId, $customerId, $customer, $data) {
            $debt = $this->debtRepository->create([
                'shop_id' => $shopId,
                'customer_id' => $customerId,
                'amount' => $data['amount'],
                'type' => $data['type'] ?? 'manual',
                'description' => $data['description'] ?? null,
            ]);

            AuditLog::log($shopId, 'debt.added', [
                'debt_id' => $debt->id,
                'customer_id' => $customerId,
                'customer_name' => $customer->name,
                'amount' => $data['amount'],
                'type' => $data['type'] ?? 'manual',
            ]);

            return $debt;
        });
    }

    public function registerPayment(string $shopId, string $customerId, array $data): ?Debt
    {
        $customer = $this->customerRepository->getByShopIdAndId($shopId, $customerId);
        
        if (!$customer) {
            return null;
        }

        return DB::transaction(function () use ($shopId, $customerId, $customer, $data) {
            $debt = $this->debtRepository->create([
                'shop_id' => $shopId,
                'customer_id' => $customerId,
                'amount' => $data['amount'],
                'type' => 'payment',
                'description' => $data['description'] ?? 'Payment received',
            ]);

            AuditLog::log($shopId, 'payment.registered', [
                'debt_id' => $debt->id,
                'customer_id' => $customerId,
                'customer_name' => $customer->name,
                'amount' => $data['amount'],
            ]);

            return $debt;
        });
    }
}
