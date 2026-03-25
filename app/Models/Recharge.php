<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @OA\Schema(
 *     schema="Recharge",
 *     required={"id", "shop_id", "phone", "operator", "amount", "status"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="shop_id", type="string", format="uuid"),
 *     @OA\Property(property="customer_id", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="operator", type="string"),
 *     @OA\Property(property="amount", type="number", format="decimal"),
 *     @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}),
 *     @OA\Property(property="reference_code", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Recharge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'customer_id',
        'phone',
        'operator',
        'amount',
        'offer',
        'as_debt',
        'status',
        'reference_code',
        'idempotency_key',
        'gateway_response',
        'gateway_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'as_debt' => 'boolean',
        'gateway_response' => 'array',
    ];

    protected $hidden = [
        'idempotency_key',
        'gateway_response',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(RechargeTransaction::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsSuccess(): void
    {
        $this->update(['status' => 'success']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsBalanceError(): void
    {
        $this->update(['status' => 'balance_error']);
    }

    public function markAsRejected(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isBalanceError(): bool
    {
        return $this->status === 'balance_error';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['success', 'failed', 'balance_error', 'rejected', 'cancelled']);
    }
}
