<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Tenant\app\Models\Store;
use Modules\Tenant\app\Services\StoreOnboardingService;

final class StoreOnboardingController extends Controller
{
    public function __construct(
        private readonly StoreOnboardingService $onboarding,
    ) {}

    /**
     * POST /api/v1/tenant/stores/{store}/complete-onboarding
     */
    public function completeOnboarding(Store $store): JsonResponse
    {
        Gate::authorize('update', $store);

        try {
            $store = $this->onboarding->completeOnboarding($store, auth()->user());
        } catch (DomainException $exception) {
            return $this->conflict($exception);
        }

        return $this->respond($store);
    }

    /**
     * POST /api/v1/tenant/stores/{store}/publish
     */
    public function publish(Store $store): JsonResponse
    {
        Gate::authorize('publish', $store);

        try {
            $store = $this->onboarding->publish($store, auth()->user());
        } catch (DomainException $exception) {
            return $this->conflict($exception);
        }

        return $this->respond($store);
    }

    /**
     * POST /api/v1/tenant/stores/{store}/unpublish
     */
    public function unpublish(Store $store): JsonResponse
    {
        Gate::authorize('publish', $store);

        $store = $this->onboarding->unpublish($store, auth()->user());

        return $this->respond($store);
    }

    private function conflict(DomainException $exception): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'STORE_STATE_CONFLICT', 'message' => $exception->getMessage()],
        ], 409);
    }

    private function respond(Store $store): JsonResponse
    {
        return response()->json(['data' => [
            'id' => $store->id,
            'status' => $store->status,
            'is_published' => $store->is_published,
        ]]);
    }
}