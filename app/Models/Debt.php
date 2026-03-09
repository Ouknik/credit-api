<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Debt",
 *     required={"id", "shop_id", "customer_id", "amount", "type"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="shop_id", type="string", format="uuid"),
 *     @OA\Property(property="customer_id", type="string", format="uuid"),
 *     @OA\Property(property="amount", type="number", format="decimal"),
 *     @OA\Property(property="type", type="string", enum={"manual", "recharge", "payment"}),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Debt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'customer_id',
        'amount',
        'type',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::created(function (Debt $debt) {
            $debt->customer->updateTotalDebt();
        });

        static::updated(function (Debt $debt) {
            $debt->customer->updateTotalDebt();
        });

        static::deleted(function (Debt $debt) {
            $debt->customer->updateTotalDebt();
        });
    }
}
