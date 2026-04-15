<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @OA\Schema(
 *     schema="Shop",
 *     required={"id", "name", "phone", "status"},
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="balance", type="number", format="decimal"),
 *     @OA\Property(property="status", type="string", enum={"active", "suspended"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Shop extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids;

    public const ROLE_SHOP_OWNER = 'shop_owner';
    public const ROLE_DISTRIBUTOR = 'distributor';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'balance',
        'status',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'shop_id' => $this->id,
            'phone' => $this->phone,
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function recharges(): HasMany
    {
        return $this->hasMany(Recharge::class);
    }

    public function procurementOrders(): HasMany
    {
        return $this->hasMany(ProcurementOrder::class);
    }

    public function procurementOffers(): HasMany
    {
        return $this->hasMany(ProcurementOffer::class, 'distributor_shop_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isShopOwner(): bool
    {
        return $this->hasRole(self::ROLE_SHOP_OWNER);
    }

    public function isDistributor(): bool
    {
        return $this->hasRole(self::ROLE_DISTRIBUTOR);
    }

    public function hasEnoughBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
