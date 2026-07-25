<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Identity\app\Models\StoreStaff;

/**
 * The tenant. Every tenant-owned model elsewhere in the platform
 * (Product, Order, ...) carries `store_id` referencing this table and
 * is auto-filtered by TenantScope (App\Support\Tenancy) — but Store
 * itself is platform-level, not tenant-scoped by itself.
 *
 * `status` and `is_published` are independent, on purpose (§8.1):
 * status is the Super-Admin-controlled lifecycle (pending_setup ->
 * active -> suspended/closed); is_published is owner-controlled and
 * gated to only be settable while status = active. See publish()
 * below and the DB check constraint in the migration — this rule is
 * enforced twice, deliberately.
 */
final class Store extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_PENDING_SETUP = 'pending_setup';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'domain',
        'status',
        'is_published',
        'isolation_mode',
        'settings',
        'suspended_at',
        'suspension_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'settings' => 'array',
            'suspended_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class, 'store_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(StoreStaff::class, 'store_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The application-layer half of the publish gate. The DB check
     * constraint (migration) is the backstop; this is what actually
     * runs on the happy path and gives a meaningful error message
     * instead of a raw constraint-violation exception bubbling up.
     */
    public function canBePublished(): bool
    {
        return $this->isActive();
    }
}