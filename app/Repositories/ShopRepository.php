<?php

namespace App\Repositories;

use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class ShopRepository extends BaseRepository
{
    public function __construct(Shop $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Shop
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?Shop
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function lockForUpdate(string $id): ?Shop
    {
        return $this->model->lockForUpdate()->find($id);
    }

    public function deductBalance(string $shopId, float $amount): bool
    {
        return DB::transaction(function () use ($shopId, $amount) {
            $shop = $this->lockForUpdate($shopId);
            
            if (!$shop || !$shop->hasEnoughBalance($amount)) {
                return false;
            }

            $shop->balance -= $amount;
            return $shop->save();
        });
    }

    public function addBalance(string $shopId, float $amount): bool
    {
        return DB::transaction(function () use ($shopId, $amount) {
            $shop = $this->lockForUpdate($shopId);
            
            if (!$shop) {
                return false;
            }

            $shop->balance += $amount;
            return $shop->save();
        });
    }
}
