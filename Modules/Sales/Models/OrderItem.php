<?php

namespace Modules\Sales\Models;

use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\ProductVariant;

class OrderItem extends Model
{
    use HasFactory, HasUlids;

    // Not BelongsToTenant — scoped transitively through its parent Order,
    // same rationale as CartItem.

    protected $fillable = [
        'order_id',
        'variant_id',
        'product_title_snapshot',
        'sku_snapshot',
        'options_snapshot',
        'quantity',
        'unit_price_cents',
        'total_cents',
    ];

    protected $casts = [
        'options_snapshot' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * May return null if the variant was later deleted — always prefer the
     * *_snapshot columns for display; only use this relation for things like
     * "go back to this product" links, and null-check accordingly.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function unitPrice(): Money
    {
        return Money::fromMinorUnits($this->unit_price_cents, $this->order->currency);
    }
}