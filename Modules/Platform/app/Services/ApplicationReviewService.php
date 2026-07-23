<?php

declare(strict_types=1);

namespace Modules\Platform\app\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Platform\app\Events\ApplicationApproved;
use Modules\Platform\app\Events\ApplicationInfoRequested;
use Modules\Platform\app\Events\ApplicationRejected;
use Modules\Platform\app\Models\ApplicationReview;
use Modules\Platform\app\Models\AuditLog;
use Modules\Platform\app\Models\MerchantApplication;

/**
 * Owns every state transition a Super Admin can make on a
 * MerchantApplication. This is the one place that:
 *
 *  - enforces which transitions are legal from which status,
 *  - writes the immutable ApplicationReview record,
 *  - writes the AuditLog entry,
 *  - dispatches the corresponding domain event,
 *
 * all inside a single DB transaction. Both the Filament resource
 * actions (this task) and, later, any API endpoint doing the same
 * thing call into this service rather than duplicating the logic —
 * exactly the "Application Services" pattern from architecture §1.
 */
final class ApplicationReviewService
{
    /**
     * @throws DomainException if the application isn't in a reviewable state
     */
    public function approve(MerchantApplication $application, User $reviewer, ?string $notes = null): MerchantApplication
    {
        $this->assertReviewable($application);

        return DB::transaction(function () use ($application, $reviewer, $notes): MerchantApplication {
            $previousStatus = $application->status;

            $application->update([
                'status' => MerchantApplication::STATUS_APPROVED,
                'decided_at' => now(),
            ]);

            $this->recordReview($application, $reviewer, ApplicationReview::ACTION_APPROVE, $notes);
            $this->recordAudit($application, $reviewer, 'merchant_application.approved', $previousStatus, $application->status);

            ApplicationApproved::dispatch($application);

            return $application;
        });
    }

    /**
     * @throws DomainException if the application isn't in a reviewable state
     */
    public function reject(MerchantApplication $application, User $reviewer, string $reason): MerchantApplication
    {
        $this->assertReviewable($application);

        return DB::transaction(function () use ($application, $reviewer, $reason): MerchantApplication {
            $previousStatus = $application->status;

            $application->update([
                'status' => MerchantApplication::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'decided_at' => now(),
            ]);

            $this->recordReview($application, $reviewer, ApplicationReview::ACTION_REJECT, $reason);
            $this->recordAudit($application, $reviewer, 'merchant_application.rejected', $previousStatus, $application->status);

            ApplicationRejected::dispatch($application);

            return $application;
        });
    }

    /**
     * @throws DomainException if the application isn't in a reviewable state
     */
    public function requestInfo(MerchantApplication $application, User $reviewer, string $notes): MerchantApplication
    {
        $this->assertReviewable($application);

        return DB::transaction(function () use ($application, $reviewer, $notes): MerchantApplication {
            $previousStatus = $application->status;

            $application->update([
                'status' => MerchantApplication::STATUS_INFO_REQUESTED,
            ]);

            $this->recordReview($application, $reviewer, ApplicationReview::ACTION_REQUEST_INFO, $notes);
            $this->recordAudit($application, $reviewer, 'merchant_application.info_requested', $previousStatus, $application->status);

            ApplicationInfoRequested::dispatch($application);

            return $application;
        });
    }

    /**
     * Admin decisions are only valid while an application is actively
     * awaiting review. `info_requested` is deliberately excluded: at
     * that point the ball is in the merchant's court (they must
     * resubmit before it returns to `under_review`), so re-deciding
     * on it here would race with — or short-circuit — that resubmission.
     */
    private function assertReviewable(MerchantApplication $application): void
    {
        $reviewableStatuses = [
            MerchantApplication::STATUS_SUBMITTED,
            MerchantApplication::STATUS_UNDER_REVIEW,
        ];

        if (! in_array($application->status, $reviewableStatuses, true)) {
            throw new DomainException(
                "Application [{$application->id}] cannot be reviewed from status [{$application->status}]."
            );
        }
    }

    private function recordReview(
        MerchantApplication $application,
        User $reviewer,
        string $action,
        ?string $notes,
    ): ApplicationReview {
        return ApplicationReview::create([
            'application_id' => $application->id,
            'reviewer_id' => $reviewer->id,
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    private function recordAudit(
        MerchantApplication $application,
        User $reviewer,
        string $action,
        string $oldStatus,
        string $newStatus,
    ): AuditLog {
        return AuditLog::record(
            actor: $reviewer,
            action: $action,
            auditable: $application,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
        );
    }
}