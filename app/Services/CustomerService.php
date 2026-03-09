<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository
    ) {}

    public function getCustomersByShop(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepository->paginateByShopId($shopId, $perPage, $filters);
    }

    public function getCustomer(string $shopId, string $customerId): ?Customer
    {
        return $this->customerRepository->getByShopIdAndId($shopId, $customerId);
    }

    public function createCustomer(string $shopId, array $data): Customer
    {
        $customer = $this->customerRepository->create([
            'shop_id' => $shopId,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'is_trusted' => $data['is_trusted'] ?? false,
            'daily_limit' => $data['daily_limit'] ?? null,
            'monthly_limit' => $data['monthly_limit'] ?? null,
            'max_debt_limit' => $data['max_debt_limit'] ?? null,
        ]);

        AuditLog::log($shopId, 'customer.created', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
        ]);

        return $customer;
    }

    public function updateCustomer(string $shopId, string $customerId, array $data): ?Customer
    {
        $customer = $this->getCustomer($shopId, $customerId);

        if (!$customer) {
            return null;
        }

        $customer->update($data);

        AuditLog::log($shopId, 'customer.updated', [
            'customer_id' => $customer->id,
            'changes' => $data,
        ]);

        return $customer->fresh();
    }

    public function deleteCustomer(string $shopId, string $customerId): bool
    {
        $customer = $this->getCustomer($shopId, $customerId);

        if (!$customer) {
            return false;
        }

        AuditLog::log($shopId, 'customer.deleted', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
        ]);

        return $customer->delete();
    }

    public function getCustomerStats(string $shopId): array
    {
        return [
            'total_customers' => $this->customerRepository->countByShopId($shopId),
            'total_debt' => $this->customerRepository->getTotalDebtByShopId($shopId),
        ];
    }
}
