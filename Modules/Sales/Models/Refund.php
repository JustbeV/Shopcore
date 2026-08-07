<?php

namespace Modules\Sales\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payments\Models\Payment;

class Refund extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = [
        'order_id',
        'payment_id',
        'requested_by_customer_id',
        'status',
        'amount_cents',
        'reason',
        'decision_note',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}