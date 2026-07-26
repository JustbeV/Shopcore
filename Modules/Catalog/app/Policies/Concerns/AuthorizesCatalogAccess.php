<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Policies\Concerns;

use App\Models\User;
use Modules\Tenant\app\Models\Store;

/**
 * Every Catalog policy (Product, Category, Collection) asks the same
 * question: "can this user manage this store's catalog?". Rather than
 * four near-identical policy classes each re-deriving owner/staff/
 * permission logic (and drifting out of sync with StorePolicy's
 * version of the same check), it lives here once.
 */
trait AuthorizesCatalogAccess
{
    private function canManageCatalog(User $user, string $storeId): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $store = Store::query()->find($storeId);

        if ($store !== null && $user->id === $store->owner_id) {
            return true;
        }

        setPermissionsTeamId($storeId);

        return $user->can('catalog.manage');
    }
}