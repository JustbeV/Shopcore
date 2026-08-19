<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'owner_id', 'name', 'slug', 'domain', 'status',
        'is_published', 'isolation_mode', 'settings',
        'suspended_at', 'suspension_reason',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'settings' => 'array',
        'suspended_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}