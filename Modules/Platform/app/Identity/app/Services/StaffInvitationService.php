<?php

declare(strict_types=1);

namespace Modules\Identity\app\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Identity\app\Events\StaffInvited;
use Modules\Identity\app\Events\StaffJoined;
use Modules\Identity\app\Events\StaffRevoked;
use Modules\Identity\app\Models\StoreStaff;
use Modules\Identity\app\Notifications\StaffInvitationNotification;

final class StaffInvitationService
{
    public function invite(string $storeId, User $invitedUser, User $inviter): StoreStaff
    {
        $staff = StoreStaff::create([
            'store_id' => $storeId,
            'user_id' => $invitedUser->id,
            'invited_by' => $inviter->id,
            'status' => StoreStaff::STATUS_INVITED,
            'invitation_token' => Str::random(40),
            'invited_at' => now(),
        ]);

        $invitedUser->notify(new StaffInvitationNotification($staff));
        StaffInvited::dispatch($staff);

        return $staff;
    }

    public function accept(StoreStaff $staff, string $token): StoreStaff
    {
        if (! hash_equals($staff->invitation_token ?? '', $token)) {
            abort(403, 'Invalid or expired invitation token.');
        }

        if (! $staff->isPendingInvitation()) {
            abort(422, 'Invitation is no longer active.');
        }

        $staff->update([
            'status' => StoreStaff::STATUS_ACTIVE,
            'invitation_token' => null,
            'joined_at' => now(),
        ]);

        StaffJoined::dispatch($staff);

        return $staff;
    }

    public function revoke(StoreStaff $staff): StoreStaff
    {
        $staff->update([
            'status' => StoreStaff::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        StaffRevoked::dispatch($staff);

        return $staff;
    }
}