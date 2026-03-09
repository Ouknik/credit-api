<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="AuditLog",
 *     required={"id", "shop_id", "action"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="shop_id", type="string", format="uuid"),
 *     @OA\Property(property="action", type="string"),
 *     @OA\Property(property="payload", type="object"),
 *     @OA\Property(property="ip_address", type="string"),
 *     @OA\Property(property="user_agent", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'action',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public static function log(string $shopId, string $action, array $payload = [], ?string $ip = null, ?string $userAgent = null): self
    {
        return static::create([
            'shop_id' => $shopId,
            'action' => $action,
            'payload' => $payload,
            'ip_address' => $ip ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
