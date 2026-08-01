<?php

namespace Modules\Sales\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\CRM\Models\Customer;
use Modules\Payments\Models\Payment;

class Order extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'subtotal_cents',
        'tax_cents',
        'shipping_cents',
        'discount_cents',
        'total_cents',
        'currency',
        'shipping_address',
        'billing_address',
        'idempotency_key',
        'placed_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'placed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    public function total(): Money
    {
        return Money::fromMinorUnits($this->total_cents, $this->currency);
    }

    /**
     * Records a status change AND performs it in one call — keeps the audit
     * trail from ever drifting out of sync with the actual column, since
     * there's exactly one code path that mutates `status`.
     */
    public function transitionTo(string $newStatus, ?string $changedBy = null, ?string $note = null): void
    {
        $from = $this->status;

        $this->update(['status' => $newStatus]);

        $this->statusHistory()->create([
            'from_status' => $from,
            'to_status' => $newStatus,
            'changed_by' => $changedBy,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
