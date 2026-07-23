<?php

declare(strict_types=1);

namespace Modules\Platform\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A prospective merchant's application to open a store.
 *
 * Deliberately NOT tenant-scoped (no BelongsToTenant trait): a
 * MerchantApplication exists *before* any Store does, and Super
 * Admins must be able to query across all applications regardless of
 * tenant. See architecture doc §7.2 for the full ERD and §6.1 for the
 * approval workflow this feeds into.
 *
 * Lifecycle (enforced by the service/state layer, not this model):
 *   submitted -> under_review -> approved
 *                             -> rejected
 *                             -> info_requested -> under_review (resubmit)
 */
final class MerchantApplication extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_INFO_REQUESTED = 'info_requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'merchant_applications';

    /**
     * Mass-assignment allow-list. Never $guarded = [] — see
     * architecture doc §13 (mass assignment mitigation).
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'metadata',
        'status',
        'rejection_reason',
        'submitted_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * The user who submitted the application. Note: this is
     * App\Models\User (the base authenticatable), not a
     * Modules\Identity model — Identity formalizes roles/permissions
     * on top of it but does not replace it. See Modules/Identity
     * README once that module ships.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * TODO: Modules\Platform\app\Models\ApplicationReview ships with
     * the "Application Review & Approval" task, not this one. Left
     * here as a forward reference matching the ERD (§7.2) — safe
     * because PHP resolves the class lazily at call time, not at
     * parse time, so this file loads fine before that model exists.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ApplicationReview::class, 'application_id');
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_INFO_REQUESTED,
        ], true);
    }
}