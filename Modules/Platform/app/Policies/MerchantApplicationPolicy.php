<?php

declare(strict_types=1);

namespace Modules\Platform\app\Policies;

use App\Models\User;
use Modules\Platform\app\Models\MerchantApplication;

/**
 * Namespace mirrors Modules\Platform\app\Models -> Policies, so
 * Laravel 12's convention-based policy auto-discovery resolves this
 * without manual registration. It is also registered explicitly in
 * App\Providers\AuthServiceProvider as defense in depth (auto-discovery
 * silently no-ops if the convention ever drifts, e.g. a future
 * refactor of the module's folder layout).
 *
 * NOTE: `hasRole('super_admin')` assumes the Identity module's RBAC
 * (spatie/laravel-permission with the `super_admin` role seeded) —
 * that module hasn't been built yet in this task sequence. Once it
 * ships, this is the only file that needs no changes, since it was
 * already written against that contract.
 */
final class MerchantApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, MerchantApplication $application): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Single ability covering approve/reject/request-info — all three
     * are "can this admin decide on this application", not
     * independently grantable permissions. Splitting them into three
     * abilities would let you grant "approve but not reject", which
     * is not a real-world permission split this platform needs.
     */
    public function review(User $user, MerchantApplication $application): bool
    {
        return $user->hasRole('super_admin');
    }
}