<?php

namespace Modules\Sales\Models;

use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\ProductVariant;

class CartItem extends Model
{
    use HasFactory, HasUlids;

    // Note: CartItem is intentionally NOT BelongsToTenant. It has no store_id
    // column of its own — it's scoped transitively through its parent Cart,
    // which is the tenant-owned entity. Always query cart items via
    // $cart->items(), never CartItem::query() directly.

    protected $fillable = [
        'cart_id',
        'variant_id',
        'quantity',
        'unit_price_cents',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function unitPrice(): Money
    {
        return Money::fromMinorUnits($this->unit_price_cents, $this->cart->currency);
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice()->multiply($this->quantity);
    }
}
