<?php

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\CRM\Models\Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'customer_name' => $this->customer->first_name,
            'rating' => $this->rating,
            'body' => $this->body,
            'status' => $this->status,
            'verified_purchase' => $this->isVerifiedPurchase(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}