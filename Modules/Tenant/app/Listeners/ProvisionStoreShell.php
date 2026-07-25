<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Modules\Platform\app\Events\ApplicationApproved;
use Modules\Tenant\app\Models\Store;

/**
 * Creates the `stores` shell record the moment an application is
 * approved (architecture §6.1/§8.1). This is the listener the
 * ApplicationApproved event's docblock (Task 2) said would exist
 * "when it lands" — it has landed. Tenant depends on Platform's event
 * here (imports ApplicationApproved); Platform has zero knowledge of
 * Tenant, preserving one-way coupling.
 *
 * Status starts at `pending_setup`, is_published stays false — both
 * enforced by the DB check constraint regardless of this listener's
 * correctness.
 */
final class ProvisionStoreShell implements ShouldQueue
{
    public function handle(ApplicationApproved $event): void
    {
        $application = $event->application;
        $proposedName = $application->metadata['proposed_store_name'] ?? $application->business_name;

        Store::create([
            'owner_id' => $application->user_id,
            'name' => $proposedName,
            'slug' => $this->uniqueSlug($proposedName),
            'domain' => $this->uniqueSlug($proposedName).'.'.config('tenancy.base_domain'),
            'status' => Store::STATUS_PENDING_SETUP,
            'is_published' => false,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Store::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}