<?php

declare(strict_types=1);

namespace Modules\Platform\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Platform\app\Http\Requests\SubmitApplicationRequest;
use Modules\Platform\app\Models\MerchantApplication;

/**
 * Accepts and validates merchant application submissions.
 *
 * Scope intentionally limited to "accept + persist" for this task —
 * no email verification gating, no ApplicationSubmitted event, no
 * admin notification, no MerchantApplicationService yet. Those land
 * in the next task (see architecture doc §6.1 sequence diagram for
 * the full flow this is the first step of); wiring them in now would
 * be building ahead of what's been reviewed.
 */
final class MerchantApplicationController extends Controller
{
    /**
     * POST /api/v1/platform/merchant-applications
     */
    public function store(SubmitApplicationRequest $request): JsonResponse
    {
        $application = DB::transaction(function () use ($request): MerchantApplication {
            return MerchantApplication::create([
                ...$request->validatedApplicationData(),
                'user_id' => $request->user()->id,
                'status' => MerchantApplication::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
        });

        return response()->json([
            'data' => [
                'id' => $application->id,
                'business_name' => $application->business_name,
                'business_type' => $application->business_type,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
            ],
        ], 201);
    }
}