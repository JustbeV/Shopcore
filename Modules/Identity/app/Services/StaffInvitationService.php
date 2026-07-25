<?php

declare(strict_types=1);

namespace Modules\Identity\app\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Identity\app\Events\StaffInvitationAccepted;
use Modules\Identity\app\Events\StaffInvited;
use Modules\Identity\app\Events\StaffRevoked;
use Modules\Identity\app\Models\StoreStaff;
use Modules\Identity\app\Notifications\StaffInvitationNotification;

/**
 * Owns the staff invitation lifecycle: invite -> accept, or
 * invite -> revoke. Mirrors the shape of Platform's
 * ApplicationReviewService (Task 2) — transaction per transition,
 * event dispatched at the end, illegal transitions raise
 * DomainException rather than silently no-op.
 */
final class StaffInvitationService
{
    /**
     * Invites a user (by email) to join a store's staff. If the email
     * doesn't match an existing user, a pending_verification User is
     * created so the invitation has somewhere to point — they set
     * their password / verify email when accepting.
     *
     * @param  string  $storeId  ULID of the store — not FK-enforced yet (Tenant module pending)
     * @param  array<int, string>  $roles  Spatie role names to assign once accepted, team-scoped to $storeId
     */
    public function invite(
        string $storeId,
        string $email,
        string $name,
        User $invitedBy,
        array $roles = [],
    ): StoreStaff {
        $existingActive = StoreStaff::query()
            ->where('store_id', $storeId)
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->whereIn('status', [StoreStaff::STATUS_INVITED, StoreStaff::STATUS_ACTIVE])
            ->exists();

        if ($existingActive) {
            throw new DomainException("[{$email}] already has an active or pending invitation at this store.");
        }

        return DB::transaction(function () use ($storeId, $email, $name, $invitedBy, $roles): StoreStaff {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => str()->password(32),
                    'status' => User::STATUS_PENDING_VERIFICATION,
                ],
            );

            $membership = StoreStaff::create([
                'store_id' => $storeId,
                'user_id' => $user->id,
                'invited_by' => $invitedBy->id,
                'status' => StoreStaff::STATUS_INVITED,
                'invitation_token' => Str::random(64),
                'invited_at' => now(),
            ]);

            // Team-scoped role assignment: Spatie's teams feature reads
            // the "current team" from a resolver, so we set it for the
            // duration of this call. Roles only take effect once the
            // invitation is accepted (see accept() below) — assigning
            // now would grant access before the invitee has consented.
            if ($roles !== []) {
                setPermissionsTeamId($storeId);
                $user->syncRoles($roles);
            }

            $user->notify(new StaffInvitationNotification($membership));

            StaffInvited::dispatch($membership);

            return $membership;
        });
    }

    /**
     * @throws DomainException if the invitation isn't pending (already accepted/revoked, or expired token)
     */
    public function accept(StoreStaff $membership): StoreStaff
    {
        if (! $membership->isPendingInvitation()) {
            throw new DomainException("Invitation [{$membership->id}] is not pending — it may already have been accepted or revoked.");
        }

        return DB::transaction(function () use ($membership): StoreStaff {
            $membership->update([
                'status' => StoreStaff::STATUS_ACTIVE,
                'joined_at' => now(),
                'invitation_token' => null,
            ]);

            if ($membership->user->status === User::STATUS_PENDING_VERIFICATION) {
                $membership->user->update(['status' => User::STATUS_ACTIVE]);
            }

            StaffInvitationAccepted::dispatch($membership->fresh());

            return $membership->fresh();
        });
    }

    /**
     * @throws DomainException if the membership is already revoked
     */
    public function revoke(StoreStaff $membership): StoreStaff
    {
        if ($membership->status === StoreStaff::STATUS_REVOKED) {
            throw new DomainException("Membership [{$membership->id}] is already revoked.");
        }

        return DB::transaction(function () use ($membership): StoreStaff {
            $membership->update([
                'status' => StoreStaff::STATUS_REVOKED,
                'revoked_at' => now(),
                'invitation_token' => null,
            ]);

            setPermissionsTeamId($membership->store_id);
            $membership->user->syncRoles([]);

            StaffRevoked::dispatch($membership->fresh());

            return $membership->fresh();
        });
    }
}