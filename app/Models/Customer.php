<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Customer",
 *     required={"id", "shop_id", "name", "phone"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="shop_id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="address", type="string"),
 *     @OA\Property(property="total_debt", type="number", format="decimal"),
 *     @OA\Property(property="is_trusted", type="boolean"),
 *     @OA\Property(property="daily_limit", type="number", format="decimal"),
 *     @OA\Property(property="monthly_limit", type="number", format="decimal"),
 *     @OA\Property(property="max_debt_limit", type="number", format="decimal"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Customer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'address',
        'total_debt',
        'is_trusted',
        'daily_limit',
        'monthly_limit',
        'max_debt_limit',
    ];

    protected $casts = [
        'total_debt' => 'decimal:2',
        'is_trusted' => 'boolean',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'max_debt_limit' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class);
    }

    public function canTakeDebt(float $amount): bool
    {
        if ($this->max_debt_limit === null) {
            return true;
        }
        return ($this->total_debt + $amount) <= $this->max_debt_limit;
    }

    public function updateTotalDebt(): void
    {
        $this->total_debt = $this->debts()
            ->selectRaw("SUM(CASE WHEN type = 'payment' THEN -amount ELSE amount END) as total")
            ->value('total') ?? 0;
        $this->save();
    }
}
