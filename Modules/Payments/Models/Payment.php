<?php

namespace Modules\Payments\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sales\Models\Order;

class Payment extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = [
        'order_id',
        'provider',
        'status',
        'provider_reference',
        'client_secret',
        'amount_cents',
        'currency',
        'failure_reason',
    ];

    protected $casts = [
        'failure_reason' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function amount(): Money
    {
        return Money::fromMinorUnits($this->amount_cents, $this->currency);
    }
}
