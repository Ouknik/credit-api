<?php

namespace App\Repositories;

use App\Models\Recharge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class RechargeRepository extends BaseRepository
{
    public function __construct(Recharge $model)
    {
        parent::__construct($model);
    }

    public function findByIdempotencyKey(string $key): ?Recharge
    {
        return $this->model->where('idempotency_key', $key)->first();
    }

    public function findByReferenceCode(string $code): ?Recharge
    {
        return $this->model->where('reference_code', $code)->first();
    }

    public function paginateByShopId(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model
            ->where('shop_id', $shopId)
            ->with('customer');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['operator'])) {
            $query->where('operator', $filters['operator']);
        }

        if (!empty($filters['phone'])) {
            $query->where('phone', 'like', "%{$filters['phone']}%");
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getTodayRechargesByShopId(string $shopId): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereDate('created_at', Carbon::today())
            ->get();
    }

    public function getTodayRechargesCountByShopId(string $shopId): int
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    public function getTodayRechargesAmountByShopId(string $shopId): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('status', 'success')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');
    }

    public function getDailyRechargesByShopId(string $shopId, Carbon $date): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereDate('created_at', $date)
            ->get();
    }

    public function getMonthlyRechargesByShopId(string $shopId, int $year, int $month): Collection
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();
    }

    public function getSuccessfulRechargesAmountByShopIdAndPeriod(string $shopId, Carbon $from, Carbon $to): float
    {
        return $this->model
            ->where('shop_id', $shopId)
            ->where('status', 'success')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');
    }

    public function countByShopIdAndPeriod(string $shopId, Carbon $from, Carbon $to, ?string $status = null): int
    {
        $query = $this->model
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to]);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
