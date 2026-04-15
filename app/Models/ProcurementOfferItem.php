<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementOfferItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'procurement_offer_id',
        'procurement_order_item_id',
        'product_id',
        'is_available',
        'unit_price',
        'quantity',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'subtotal' => 'decimal:2',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProcurementOffer::class, 'procurement_offer_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementOrderItem::class, 'procurement_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
