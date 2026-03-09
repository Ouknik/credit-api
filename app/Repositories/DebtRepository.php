<?php

namespace App\Repositories;

use App\Models\Debt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class DebtRepository extends BaseRepository
{
    public function __construct(Debt $model)
    {
        parent::__construct($model);
    }

    public function paginateByShopId(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model
            ->where('shop_id', $shopId)
            ->with('customer');

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getByCustomerId(string $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTotalByShopId(string $shopId): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereIn('type', ['manual', 'recharge'])
            ->sum('amount');
    }

    public function getTotalPaymentsByShopId(string $shopId): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('type', 'payment')
            ->sum('amount');
    }

    public function getDailyDebtsByShopId(string $shopId, Carbon $date): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereIn('type', ['manual', 'recharge'])
            ->whereDate('created_at', $date)
            ->sum('amount');
    }

    public function getMonthlyDebtsByShopId(string $shopId, int $year, int $month): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereIn('type', ['manual', 'recharge'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');
    }
}
