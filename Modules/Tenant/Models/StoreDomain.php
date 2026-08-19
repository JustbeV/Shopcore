<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    use HasUlids;

    protected $fillable = ['store_id', 'hostname', 'is_primary', 'verification_status', 'verification_token', 'verified_at'];

    protected $casts = ['is_primary' => 'boolean', 'verified_at' => 'datetime'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}