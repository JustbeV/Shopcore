<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Sales\Models\Cart
 */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'total_quantity' => $this->totalQuantity(),
            'subtotal_cents' => $this->subtotal()->amountMinor(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
