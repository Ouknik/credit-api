<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_number',
        'shop_id',
        'status',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'preferred_delivery_time',
        'notes',
        'confirmation_pin',
    ];

    protected $casts = [
        'delivery_lat' => 'decimal:7',
        'delivery_lng' => 'decimal:7',
        'preferred_delivery_time' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementOrderItem::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ProcurementOffer::class);
    }
}
