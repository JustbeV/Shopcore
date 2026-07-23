<?php

declare(strict_types=1);

namespace Modules\Platform\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Platform-wide audit trail for sensitive mutations (application
 * decisions, store suspensions, refund approvals, staff permission
 * changes, ...). Write-once, never updated or deleted from the app
 * layer — this is the thing you reach for when someone asks "who did
 * this, and when."
 */
final class AuditLog extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'audit_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Convenience recorder so services don't hand-roll the same
     * `AuditLog::create([...])` boilerplate at every call site.
     * Captures the acting user's IP from the current request when
     * available (falls back to null for console/queue contexts,
     * which have no request).
     *
     * Usage:
     *   AuditLog::record(
     *       actor: $reviewer,
     *       action: 'merchant_application.approved',
     *       auditable: $application,
     *       oldValues: ['status' => 'under_review'],
     *       newValues: ['status' => 'approved'],
     *   );
     */
    public static function record(
        ?User $actor,
        string $action,
        Model $auditable,
        array $oldValues = [],
        array $newValues = [],
    ): self {
        return self::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
        ]);
    }
}