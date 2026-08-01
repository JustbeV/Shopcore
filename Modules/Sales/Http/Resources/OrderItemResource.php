<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Sales\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_title' => $this->product_title_snapshot,
            'sku' => $this->sku_snapshot,
            'options' => $this->options_snapshot,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'total_cents' => $this->total_cents,
        ];
    }
}
