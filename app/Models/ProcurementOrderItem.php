<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementOrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'procurement_order_id',
        'product_id',
        'quantity',
        'unit',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProcurementOrder::class, 'procurement_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function offerItems(): HasMany
    {
        return $this->hasMany(ProcurementOfferItem::class);
    }
}
