<?php

namespace Modules\Sales\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Sales\Http\Requests\SaveCouponRequest;
use Modules\Sales\Http\Resources\CouponResource;
use Modules\Sales\Models\Coupon;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('coupons.manage'), 403);

        return CouponResource::collection(
            Coupon::query()->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function store(SaveCouponRequest $request)
    {
        $coupon = Coupon::query()->create($request->validated());

        return (new CouponResource($coupon))->response()->setStatusCode(201);
    }

    public function update(SaveCouponRequest $request, string $couponId)
    {
        $coupon = Coupon::query()->findOrFail($couponId);
        $coupon->update($request->validated());

        return new CouponResource($coupon->fresh());
    }

    public function destroy(Request $request, string $couponId)
    {
        abort_unless($request->user()->can('coupons.manage'), 403);

        Coupon::query()->findOrFail($couponId)->delete();

        return response()->json(['data' => ['message' => 'Coupon deleted.']]);
    }
}