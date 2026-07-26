<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\app\Models\InventoryItem;
use Modules\Catalog\app\Models\Product;
use Modules\Catalog\app\Models\ProductVariant;

/**
 * Deliberately minimal for this phase: no reserve()/release()/commit()
 * yet (architecture §8.2's checkout sequence needs those, but Sales
 * doesn't exist). What's here is just enough for a merchant to create
 * variants with starting stock and adjust it manually — the
 * reservation dance is Sales module work, not Catalog's.
 */
final class InventoryService
{
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            $variant = ProductVariant::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'sku' => $data['sku'],
                'options' => $data['options'] ?? [],
                'price_cents' => $data['price_cents'],
                'compare_at_price_cents' => $data['compare_at_price_cents'] ?? null,
                'weight' => $data['weight'] ?? null,
                'track_inventory' => $data['track_inventory'] ?? true,
            ]);

            if ($variant->track_inventory) {
                InventoryItem::create([
                    'store_id' => $product->store_id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => $data['initial_quantity'] ?? 0,
                ]);
            }

            return $variant;
        });
    }

    /**
     * Manual stock adjustment (merchant correcting a count, receiving
     * new stock, etc.) — not the order-reservation flow. $delta may be
     * negative.
     *
     * @throws DomainException if the adjustment would go negative
     */
    public function adjustStock(ProductVariant $variant, int $delta, string $reason): InventoryItem
    {
        $inventory = $variant->inventory;

        if ($inventory === null) {
            throw new DomainException("Variant [{$variant->id}] does not track inventory.");
        }

        return DB::transaction(function () use ($inventory, $delta, $reason): InventoryItem {
            $newQuantity = $inventory->quantity_on_hand + $delta;

            if ($newQuantity < 0) {
                throw new DomainException("Adjustment would result in negative stock ({$newQuantity}) — reason: {$reason}.");
            }

            $inventory->update(['quantity_on_hand' => $newQuantity]);

            // A stock_movements ledger (who adjusted what, and why) is
            // the production-grade version of this — omitted here to
            // keep this task scoped to Catalog; flagging as a gap
            // rather than silently dropping the audit trail.
            return $inventory->fresh();
        });
    }
}