<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Sales\Models\CartItem
 */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'product_title' => $this->variant->product->title,
            'variant_options' => $this->variant->options,
            'sku' => $this->variant->sku,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'line_total_cents' => $this->lineTotal()->amountMinor(),
            'image_url' => $this->variant->product->images->first()?->url,
        ];
    }
}
