<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Sales\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal_cents' => $this->subtotal_cents,
            'tax_cents' => $this->tax_cents,
            'shipping_cents' => $this->shipping_cents,
            'shipping_rate_name' => $this->shipping_rate_name_snapshot,
            'discount_cents' => $this->discount_cents,
            'coupon_code' => $this->coupon_code_snapshot,
            'total_cents' => $this->total_cents,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}