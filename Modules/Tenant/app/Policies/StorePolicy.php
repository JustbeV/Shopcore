<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Policies;

use App\Models\User;
use Modules\Tenant\app\Models\Store;

final class StorePolicy
{
    public function view(User $user, Store $store): bool
    {
        return $this->isOwnerOrStaff($user, $store);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->isOwnerOrStaff($user, $store) && $this->hasSettingsPermission($user, $store);
    }

    /**
     * Layer one of the publish gate's defense-in-depth (§8.1): even if
     * this returns true, Store::canBePublished()/the DB check
     * constraint still block the actual write while status != active.
     * This method only answers "is this user allowed to attempt it",
     * not "is it currently possible".
     */
    public function publish(User $user, Store $store): bool
    {
        return $user->id === $store->owner_id || $user->hasRole('super_admin');
    }

    public function manageDomains(User $user, Store $store): bool
    {
        return $this->isOwnerOrStaff($user, $store) && $this->hasSettingsPermission($user, $store);
    }

    private function isOwnerOrStaff(User $user, Store $store): bool
    {
        if ($user->id === $store->owner_id || $user->hasRole('super_admin')) {
            return true;
        }

        return $store->staff()->where('user_id', $user->id)->where('status', 'active')->exists();
    }

    private function hasSettingsPermission(User $user, Store $store): bool
    {
        if ($user->id === $store->owner_id || $user->hasRole('super_admin')) {
            return true;
        }

        setPermissionsTeamId($store->id);

        return $user->can('store.settings.manage');
    }
}