<?php

namespace Modules\Sales\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = ['code', 'type', 'value', 'usage_limit', 'times_used', 'is_active', 'expires_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(Money $subtotal): Money
    {
        return match ($this->type) {
            // Computed directly on minor units rather than assuming Money
            // has a divide() method — only add()/multiply()/format() were
            // documented in §9's class diagram.
            'percentage' => Money::fromMinorUnits(
                intdiv($subtotal->amountMinor() * $this->value, 100),
                $subtotal->currency(),
            ),
            'fixed' => Money::fromMinorUnits(min($this->value, $subtotal->amountMinor()), $subtotal->currency()),
            // free_shipping's discount is on the shipping line, not the
            // subtotal — CheckoutService handles that case separately.
            'free_shipping' => Money::zero($subtotal->currency()),
        };
    }
}