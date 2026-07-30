<?php

namespace Modules\Sales\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CRM\Models\Customer;

class Cart extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = [
        'customer_id',
        'session_token',
        'currency',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isGuestCart(): bool
    {
        return $this->customer_id === null;
    }

    /**
     * Sum of all line totals. Purely a display/response concern — never used
     * as the source of truth for what a customer is charged; CheckoutService
     * recomputes everything from live variant prices.
     */
    public function subtotal(): Money
    {
        return $this->items->reduce(
            fn (Money $carry, CartItem $item) => $carry->add($item->lineTotal()),
            Money::zero($this->currency),
        );
    }

    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
