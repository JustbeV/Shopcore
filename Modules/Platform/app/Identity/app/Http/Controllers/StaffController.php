<?php

declare(strict_types=1);

namespace Modules\Identity\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Identity\app\Http\Requests\InviteStaffRequest;
use Modules\Identity\app\Models\StoreStaff;
use Modules\Identity\app\Services\StaffInvitationService;

final class StaffController extends Controller
{
    public function __construct(
        private readonly StaffInvitationService $service
    ) {}

    public function invite(InviteStaffRequest $request, string $storeId): JsonResponse
    {
        $this->authorize('invite', [StoreStaff::class, $storeId]);

        $invitedUser = User::findOrFail($request->validated('user_id'));
        $staff = $this->service->invite($storeId, $invitedUser, $request->user());

        // Assign Spatie role scoped to this store
        setPermissionsTeamId($storeId);
        $invitedUser->assignRole($request->validated('role'));

        return response()->json([
            'message' => 'Staff invitation sent successfully.',
            'data' => $staff,
        ], 201);
    }

    public function accept(Request $request, StoreStaff $staff): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired signature.'], 401);
        }

        $acceptedStaff = $this->service->accept($staff, (string) $request->query('token'));

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'data' => $acceptedStaff,
        ]);
    }

    public function revoke(Request $request, StoreStaff $staff): JsonResponse
    {
        $this->authorize('revoke', $staff);

        $revokedStaff = $this->service->revoke($staff);

        return response()->json([
            'message' => 'Staff membership revoked.',
            'data' => $revokedStaff,
        ]);
    }
}