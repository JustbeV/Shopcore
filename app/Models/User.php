<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * The platform identity for Super Admins, Merchants, and Staff.
 *
 * Deliberately NOT the same identity as a storefront Customer
 * (Modules\CRM\app\Models\Customer, not built yet) — see architecture
 * doc §5.2. A merchant's staff member and a shopper buying from that
 * same merchant's store are different authentication contexts on
 * purpose, mirroring how Shopify separates staff logins from
 * customer accounts.
 *
 * `HasRoles` runs with Spatie's teams feature enabled
 * (config/permission.php: team_foreign_key = store_id), so a Staff
 * user's roles/permissions are scoped per store automatically. A
 * Super Admin or Merchant Owner's roles are assigned with a null team
 * (platform-wide / owns-everything-in-their-store respectively).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable, TwoFactorAuthenticatable;

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * NOT a Fortify contract hook — Fortify has no such automatic
     * enforcement point. This is a plain helper for our own
     * middleware/policies to call (e.g. a `RequireTwoFactor`
     * middleware on Super Admin routes) to implement the "required
     * for Super Admin, optional elsewhere" rule from architecture §6.2.
     */
    public function mustHaveTwoFactorEnabled(): bool
    {
        return $this->hasRole('super_admin');
    }
}