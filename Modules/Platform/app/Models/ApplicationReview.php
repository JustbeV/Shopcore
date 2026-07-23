<?php

declare(strict_types=1);

namespace Modules\Platform\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable record of a single admin decision on a
 * MerchantApplication. Never updated after creation — corrections are
 * made by creating a new review row, not editing an old one, so the
 * review history stays trustworthy.
 */
final class ApplicationReview extends Model
{
    use HasFactory, HasUlids;

    public const ACTION_APPROVE = 'approve';

    public const ACTION_REJECT = 'reject';

    public const ACTION_REQUEST_INFO = 'request_info';

    protected $table = 'application_reviews';

    /**
     * No `updated_at` column exists (§7.2 ERD) — reviews are
     * write-once. Only `created_at` is tracked, and Eloquent still
     * manages it automatically since $timestamps stays true.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'reviewer_id',
        'action',
        'notes',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(MerchantApplication::class, 'application_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}