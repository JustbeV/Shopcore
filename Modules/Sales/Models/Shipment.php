<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasUlids;

    // Not BelongsToTenant — scoped transitively through its parent Order.

    protected $fillable = [
        'order_id',
        'carrier',
        'tracking_number',
        'status',
        'shipped_at',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
