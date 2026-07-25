<?php

declare(strict_types=1);

namespace Modules\Identity\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the three roles from architecture §5.1. Permissions here are
 * intentionally a starter set covering only what's been built so far
 * (Task 2's application review, Task 3's staff management) — each
 * later module adds its own permissions here rather than this file
 * trying to predict the entire platform's permission list up front.
 *
 * `super_admin` and `merchant` are created with a null team (they are
 * NOT store-scoped — a merchant owner implicitly has full access to
 * their own store without needing a per-store role row). `staff` is
 * a template role name; actual staff role assignments are always
 * team-scoped to a specific store_id via setPermissionsTeamId().
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'applications.review',   // Platform: approve/reject/request-info
            'staff.manage',          // Identity: invite/revoke store staff
            'store.publish',         // Tenant: toggle is_published
            'store.settings.manage', // Tenant: branding/settings/domains
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions(Permission::all());

        // Merchant owners get store-scoped abilities implicitly via
        // StorePolicy/StoreStaffPolicy ("owner always can"), not via
        // a blanket permission grant here — so this role intentionally
        // carries no permissions of its own.
        Role::findOrCreate('merchant', 'web');

        $staff = Role::findOrCreate('staff', 'web');
        $staff->syncPermissions(['store.settings.manage']);
    }
}