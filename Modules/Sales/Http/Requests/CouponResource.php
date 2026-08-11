<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Sales\Models\Coupon
 */
class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'usage_limit' => $this->usage_limit,
            'times_used' => $this->times_used,
            'is_active' => $this->is_active,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}