<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Platform\app\Models\AuditLog;
use Modules\Tenant\app\Models\Store;

/**
 * Owns the two transitions that make up the "Demoable" outcome for
 * this phase: completing onboarding (pending_setup -> active) and
 * publishing (is_published false -> true, only once active).
 *
 * Reuses Platform's AuditLog::record() (Task 2) rather than
 * duplicating audit-trail logic — Tenant already depends on Platform
 * per the corrected dependency direction noted above.
 */
final class StoreOnboardingService
{
    /**
     * @throws DomainException if the store isn't in pending_setup
     */
    public function completeOnboarding(Store $store, User $actor, array $settings = []): Store
    {
        if ($store->status !== Store::STATUS_PENDING_SETUP) {
            throw new DomainException("Store [{$store->id}] has already completed onboarding.");
        }

        return DB::transaction(function () use ($store, $actor, $settings): Store {
            $previousStatus = $store->status;

            $store->update([
                'status' => Store::STATUS_ACTIVE,
                'settings' => array_merge($store->settings ?? [], $settings),
            ]);

            AuditLog::record(
                actor: $actor,
                action: 'store.onboarding_completed',
                auditable: $store,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => $store->status],
            );

            return $store;
        });
    }

    /**
     * Application-layer half of the publish gate. Store::canBePublished()
     * mirrors the DB check constraint from the migration — this is
     * the path that gives a clear DomainException instead of letting
     * a Postgres constraint-violation exception surface to the user.
     *
     * @throws DomainException if the store isn't active
     */
    public function publish(Store $store, User $actor): Store
    {
        if (! $store->canBePublished()) {
            throw new DomainException(
                "Store [{$store->id}] cannot be published while its status is [{$store->status}]. It must be approved and active first."
            );
        }

        return DB::transaction(function () use ($store, $actor): Store {
            $store->update(['is_published' => true]);

            AuditLog::record(
                actor: $actor,
                action: 'store.published',
                auditable: $store,
                oldValues: ['is_published' => false],
                newValues: ['is_published' => true],
            );

            return $store;
        });
    }

    public function unpublish(Store $store, User $actor): Store
    {
        return DB::transaction(function () use ($store, $actor): Store {
            $store->update(['is_published' => false]);

            AuditLog::record(
                actor: $actor,
                action: 'store.unpublished',
                auditable: $store,
                oldValues: ['is_published' => true],
                newValues: ['is_published' => false],
            );

            return $store;
        });
    }
}