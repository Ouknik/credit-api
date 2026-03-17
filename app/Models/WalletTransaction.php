<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="WalletTransaction",
 *     required={"id", "shop_id", "type", "amount", "balance_before", "balance_after"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="shop_id", type="string", format="uuid"),
 *     @OA\Property(property="type", type="string", enum={"deposit", "recharge", "refund", "adjustment"}),
 *     @OA\Property(property="amount", type="number", format="decimal"),
 *     @OA\Property(property="balance_before", type="number", format="decimal"),
 *     @OA\Property(property="balance_after", type="number", format="decimal"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="reference", type="string", nullable=true),
 *     @OA\Property(property="created_by", type="integer", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class WalletTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recharge(): BelongsTo
    {
        return $this->belongsTo(Recharge::class, 'reference', 'reference_code');
    }

    // ── Type Helpers ───────────────────────────────

    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }

    public function isRecharge(): bool
    {
        return $this->type === 'recharge';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function isAdjustment(): bool
    {
        return $this->type === 'adjustment';
    }

    /**
     * Returns true if this transaction adds money to wallet.
     */
    public function isCredit(): bool
    {
        return in_array($this->type, ['deposit', 'refund']);
    }

    /**
     * Returns true if this transaction deducts money from wallet.
     */
    public function isDebit(): bool
    {
        return in_array($this->type, ['recharge', 'adjustment']);
    }

    // ── Scopes ─────────────────────────────────────

    public function scopeForShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
