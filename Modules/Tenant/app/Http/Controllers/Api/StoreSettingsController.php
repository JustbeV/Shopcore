<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\app\Http\Requests\UpdateStoreSettingsRequest;
use Modules\Tenant\app\Models\Store;

final class StoreSettingsController extends Controller
{
    /**
     * PUT /api/v1/tenant/stores/{store}/settings
     *
     * A shallow array_merge, not a deep one: sending `branding` replaces
     * the whole `branding` sub-tree rather than merging key-by-key,
     * which keeps "remove a branding field" possible (send null) instead
     * of stale keys lingering forever under a deep merge.
     */
    public function update(UpdateStoreSettingsRequest $request, Store $store): JsonResponse
    {
        $store->update([
            'settings' => array_merge($store->settings ?? [], $request->validated()),
        ]);

        return response()->json(['data' => ['id' => $store->id, 'settings' => $store->settings]]);
    }
}