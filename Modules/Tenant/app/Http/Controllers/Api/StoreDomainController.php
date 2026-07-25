<?php

declare(strict_types=1);

namespace Modules\Tenant\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Tenant\app\Http\Requests\AddStoreDomainRequest;
use Modules\Tenant\app\Jobs\VerifyStoreDomainJob;
use Modules\Tenant\app\Models\Store;
use Modules\Tenant\app\Models\StoreDomain;

final class StoreDomainController extends Controller
{
    /**
     * POST /api/v1/tenant/stores/{store}/domains
     */
    public function store(AddStoreDomainRequest $request, Store $store): JsonResponse
    {
        $domain = StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => $request->validated('hostname'),
            'verification_token' => Str::random(32),
        ]);

        VerifyStoreDomainJob::dispatch($domain->id);

        return response()->json(['data' => [
            'id' => $domain->id,
            'hostname' => $domain->hostname,
            'verification_status' => $domain->verification_status,
            // The merchant needs this to create the DNS TXT record —
            // it's the only time it's exposed (model hides it otherwise).
            'dns_instructions' => [
                'type' => 'TXT',
                'name' => "_shopcore-verify.{$domain->hostname}",
                'value' => $domain->verification_token,
            ],
        ]], 201);
    }

    /**
     * GET /api/v1/tenant/stores/{store}/domains
     */
    public function index(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        return response()->json([
            'data' => $store->domains()->get(['id', 'hostname', 'is_primary', 'verification_status', 'verified_at']),
        ]);
    }
}