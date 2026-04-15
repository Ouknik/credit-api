<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementOffer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'procurement_order_id',
        'distributor_shop_id',
        'status',
        'total_amount',
        'delivery_cost',
        'estimated_delivery_time',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'estimated_delivery_time' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProcurementOrder::class, 'procurement_order_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'distributor_shop_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementOfferItem::class);
    }
}
