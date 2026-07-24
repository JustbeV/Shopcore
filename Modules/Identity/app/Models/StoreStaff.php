<?php

declare(strict_types=1);

namespace Modules\Identity\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's staff membership at a specific store. NOT tenant-scoped
 * via BelongsToTenant/TenantScope on purpose: a Super Admin or a
 * merchant owner legitimately needs to query staff across stores
 * (e.g. "does this user already work anywhere?"), and this table is
 * the join between the platform-level `users` table and a
 * (not-yet-built) `stores` table, not tenant-owned business data
 * itself.
 */
final class StoreStaff extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_INVITED = 'invited';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected $table = 'store_staff';

    protected $fillable = [
        'store_id',
        'user_id',
        'invited_by',
        'status',
        'invitation_token',
        'invited_at',
        'joined_at',
        'revoked_at',
    ];

    protected $hidden = [
        'invitation_token',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPendingInvitation(): bool
    {
        return $this->status === self::STATUS_INVITED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}