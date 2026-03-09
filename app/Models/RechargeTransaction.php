<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="RechargeTransaction",
 *     required={"id", "recharge_id"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="recharge_id", type="string", format="uuid"),
 *     @OA\Property(property="raw_response", type="object"),
 *     @OA\Property(property="processed_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class RechargeTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'recharge_id',
        'raw_response',
        'processed_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'processed_at' => 'datetime',
    ];

    public function recharge(): BelongsTo
    {
        return $this->belongsTo(Recharge::class);
    }
}
