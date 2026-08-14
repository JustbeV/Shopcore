<?php

namespace Modules\Shipping\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shipping\Http\Requests\SaveShippingRateRequest;
use Modules\Shipping\Http\Resources\ShippingRateResource;
use Modules\Shipping\Models\ShippingRate;

class ShippingRateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('shipping.manage'), 403);

        return ShippingRateResource::collection(ShippingRate::query()->latest()->get());
    }

    public function store(SaveShippingRateRequest $request)
    {
        $rate = ShippingRate::query()->create($request->validated());

        return (new ShippingRateResource($rate))->response()->setStatusCode(201);
    }

    public function update(SaveShippingRateRequest $request, string $rateId)
    {
        $rate = ShippingRate::query()->findOrFail($rateId);
        $rate->update($request->validated());

        return new ShippingRateResource($rate->fresh());
    }

    public function destroy(Request $request, string $rateId)
    {
        abort_unless($request->user()->can('shipping.manage'), 403);

        ShippingRate::query()->findOrFail($rateId)->delete();

        return response()->json(['data' => ['message' => 'Shipping rate deleted.']]);
    }
}