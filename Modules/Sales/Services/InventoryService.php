<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Exceptions\OutOfStockException;

class InventoryService
{
    /**
     * Atomically reserve `$quantity` units of a variant, if available.
     * Uses a single conditional UPDATE (not read-then-write) so two
     * concurrent checkouts can't both "see" the same last unit as available —
     * whichever request's UPDATE lands first wins the row lock, the second
     * simply matches zero rows and gets a clean OutOfStockException.
     *
     * @throws OutOfStockException
     */
    public function reserve(string $storeId, string $variantId, int $quantity): void
    {
        $affected = DB::table('inventory_items')
            ->where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->whereRaw('quantity_on_hand - quantity_reserved >= ?', [$quantity])
            ->update(['quantity_reserved' => DB::raw('quantity_reserved + '.(int) $quantity)]);

        if ($affected === 0) {
            $available = DB::table('inventory_items')
                ->where('store_id', $storeId)
                ->where('variant_id', $variantId)
                ->selectRaw('GREATEST(quantity_on_hand - quantity_reserved, 0) as sellable')
                ->value('sellable') ?? 0;

            throw new OutOfStockException($variantId, $quantity, (int) $available);
        }
    }

    /**
     * Give back a reservation without touching on-hand stock — used when a
     * checkout attempt fails after reserving but before payment succeeds
     * (out-of-stock on a sibling line, gateway error, or a failed webhook).
     */
    public function release(string $storeId, string $variantId, int $quantity): void
    {
        DB::table('inventory_items')
            ->where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->update(['quantity_reserved' => DB::raw('GREATEST(quantity_reserved - '.(int) $quantity.', 0)')]);
    }

    /**
     * Turn a reservation into a permanent stock decrement — called only once
     * payment has actually succeeded (ConfirmOrderPayment listener).
     */
    public function commit(string $storeId, string $variantId, int $quantity): void
    {
        DB::table('inventory_items')
            ->where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->update([
                'quantity_on_hand' => DB::raw('GREATEST(quantity_on_hand - '.(int) $quantity.', 0)'),
                'quantity_reserved' => DB::raw('GREATEST(quantity_reserved - '.(int) $quantity.', 0)'),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverses commit() — called when an approved refund puts stock back on
     * the shelf. Deliberately does NOT touch quantity_reserved (there's
     * nothing reserved for a paid, already-committed order).
     */
    public function restock(string $storeId, string $variantId, int $quantity): void
    {
        DB::table('inventory_items')
            ->where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->update([
                'quantity_on_hand' => DB::raw('quantity_on_hand + '.(int) $quantity),
                'updated_at' => now(),
            ]);
    }
}