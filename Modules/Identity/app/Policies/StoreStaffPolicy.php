<?php

declare(strict_types=1);

namespace Modules\Identity\app\Policies;

use App\Models\User;
use Modules\Identity\app\Models\StoreStaff;

final class StoreStaffPolicy
{
    public function invite(User $user, string $storeId): bool
    {
        return $user->hasRole('super_admin') || $user->hasPermissionTo('manage_staff');
    }

    public function revoke(User $user, StoreStaff $staff): bool
    {
        return $user->hasRole('super_admin') || $user->hasPermissionTo('manage_staff');
    }
}