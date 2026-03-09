<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByShopId(string $shopId): Collection
    {
        return $this->model->where('shop_id', $shopId)->get();
    }

    public function findByShopIdAndPhone(string $shopId, string $phone): ?Customer
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('phone', $phone)
            ->first();
    }

    public function paginateByShopId(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('shop_id', $shopId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_trusted'])) {
            $query->where('is_trusted', $filters['is_trusted']);
        }

        if (isset($filters['has_debt']) && $filters['has_debt']) {
            $query->where('total_debt', '>', 0);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getByShopIdAndId(string $shopId, string $customerId): ?Customer
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('id', $customerId)
            ->first();
    }

    public function countByShopId(string $shopId): int
    {
        return $this->model->where('shop_id', $shopId)->count();
    }

    public function getTotalDebtByShopId(string $shopId): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->sum('total_debt');
    }
}
