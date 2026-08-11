<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Exceptions\CheckoutException;
use Modules\Sales\Models\Coupon;

class CouponService
{
    /**
     * @throws CheckoutException
     */
    public function findValid(string $storeId, string $code): Coupon
    {
        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon || ! $coupon->isValid()) {
            throw new CheckoutException('INVALID_COUPON', 'This coupon code is invalid or has expired.');
        }

        return $coupon;
    }

    /**
     * Atomic increment with the same conditional-UPDATE pattern as
     * InventoryService::reserve() — prevents two simultaneous checkouts from
     * both redeeming the last use of a limited coupon.
     *
     * @throws CheckoutException
     */
    public function redeem(Coupon $coupon): void
    {
        $affected = DB::table('coupons')
            ->where('id', $coupon->id)
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'))
            ->increment('times_used');

        if ($affected === 0) {
            throw new CheckoutException('COUPON_LIMIT_REACHED', 'This coupon has reached its usage limit.');
        }
    }

    /**
     * Compensates a redemption when checkout fails after the coupon was
     * redeemed but before the order actually completes (gateway error).
     */
    public function release(Coupon $coupon): void
    {
        DB::table('coupons')
            ->where('id', $coupon->id)
            ->update(['times_used' => DB::raw('GREATEST(times_used - 1, 0)')]);
    }
}