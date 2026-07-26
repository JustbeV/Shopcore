<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Catalog\app\Models\Product
 *
 * First real Resource class in this codebase — Tasks 1-3 returned raw
 * arrays from controllers. Retrofitting those is out of scope here,
 * but this is the pattern any future cleanup pass should converge on:
 * never return a raw Eloquent model from a controller (architecture §11.1).
 */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'base_price_cents' => $this->base_price_cents,
            'currency' => $this->currency,
            'seo' => $this->seo,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}