<?php

namespace Modules\Shipping\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shipping\Http\Resources\ShippingRateResource;
use Modules\Shipping\Services\ShippingService;

class ShippingRateController extends Controller
{
    public function __construct(
        private readonly ShippingService $shipping,
    ) {}

    public function index(Request $request)
    {
        $request->validate(['country' => ['required', 'string', 'size:2']]);

        return ShippingRateResource::collection($this->shipping->ratesFor($request->string('country')));
    }
}