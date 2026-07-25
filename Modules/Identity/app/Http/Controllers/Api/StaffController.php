<?php

declare(strict_types=1);

namespace Modules\Identity\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Identity\app\Http\Requests\InviteStaffRequest;
use Modules\Identity\app\Models\StoreStaff;
use Modules\Identity\app\Services\StaffInvitationService;

final class StaffController extends Controller
{
    public function __construct(
        private readonly StaffInvitationService $staff,
    ) {}

    /**
     * POST /api/v1/identity/staff/invite
     */
    public function invite(InviteStaffRequest $request): JsonResponse
    {
        Gate::authorize('inviteAt', [StoreStaff::class, $request->validated('store_id')]);

        try {
            $membership = $this->staff->invite(
                storeId: $request->validated('store_id'),
                email: $request->validated('email'),
                name: $request->validated('name'),
                invitedBy: $request->user(),
                roles: $request->validated('roles', []),
            );
        } catch (DomainException $exception) {
            return response()->json(['error' => ['code' => 'STAFF_INVITE_CONFLICT', 'message' => $exception->getMessage()]], 422);
        }

        return response()->json(['data' => [
            'id' => $membership->id,
            'store_id' => $membership->store_id,
            'status' => $membership->status,
            'invited_at' => $membership->invited_at?->toIso8601String(),
        ]], 201);
    }

    /**
     * GET /api/v1/identity/staff/accept/{storeStaff} (signed URL)
     */
    public function accept(Request $request, StoreStaff $storeStaff): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['error' => ['code' => 'INVALID_OR_EXPIRED_LINK', 'message' => 'This invitation link is invalid or has expired.']], 403);
        }

        try {
            $membership = $this->staff->accept($storeStaff);
        } catch (DomainException $exception) {
            return response()->json(['error' => ['code' => 'INVITATION_NOT_PENDING', 'message' => $exception->getMessage()]], 422);
        }

        return response()->json(['data' => [
            'id' => $membership->id,
            'store_id' => $membership->store_id,
            'status' => $membership->status,
            'joined_at' => $membership->joined_at?->toIso8601String(),
        ]]);
    }

    /**
     * DELETE /api/v1/identity/staff/{storeStaff}
     */
    public function revoke(StoreStaff $storeStaff): JsonResponse
    {
        Gate::authorize('revoke', $storeStaff);

        try {
            $membership = $this->staff->revoke($storeStaff);
        } catch (DomainException $exception) {
            return response()->json(['error' => ['code' => 'ALREADY_REVOKED', 'message' => $exception->getMessage()]], 422);
        }

        return response()->json(['data' => ['id' => $membership->id, 'status' => $membership->status]]);
    }
}