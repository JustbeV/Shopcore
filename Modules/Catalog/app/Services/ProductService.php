<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\app\Models\Product;
use Modules\Tenant\app\Models\Store;

final class ProductService
{
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'status' => Product::STATUS_DRAFT,
                'base_price_cents' => $data['base_price_cents'],
                'currency' => strtoupper($data['currency']),
            ]);

            if (! empty($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update(array_filter([
                'title' => $data['title'] ?? null,
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'base_price_cents' => $data['base_price_cents'] ?? null,
                'currency' => isset($data['currency']) ? strtoupper($data['currency']) : null,
                'seo' => $data['seo'] ?? null,
            ], fn ($value) => $value !== null));

            if (array_key_exists('category_ids', $data)) {
                $product->categories()->sync($data['category_ids']);
            }

            return $product->fresh();
        });
    }

    /**
     * A product can only go live once it has at least one variant —
     * a product with nothing to sell isn't meaningfully "active".
     *
     * @throws DomainException
     */
    public function publish(Product $product): Product
    {
        if (! $product->isPublishable()) {
            throw new DomainException("Product [{$product->id}] needs at least one variant before it can be published.");
        }

        $product->update([
            'status' => Product::STATUS_ACTIVE,
            'published_at' => now(),
        ]);

        return $product;
    }

    public function archive(Product $product): Product
    {
        $product->update(['status' => Product::STATUS_ARCHIVED]);

        return $product;
    }

    public function delete(Product $product): void
    {
        // Soft delete (architecture §7.1: merchant-facing entities are
        // soft-deleted) — variants/images/inventory rows are left in
        // place rather than cascaded, since a restored product should
        // come back whole. Actual cascade-cleanup on hard delete would
        // be a scheduled purge job, not implemented here.
        $product->delete();
    }
}