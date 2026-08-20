<?php

namespace Modules\Platform\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Platform\Models\AuditLog;

class AuditLogService
{
    public function record(?User $actor, string $action, Model $auditable, array $old = [], array $new = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}