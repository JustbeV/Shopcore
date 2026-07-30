<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('cart_id')->index();
            $table->ulid('variant_id')->index();
            $table->unsignedInteger('quantity');
            // Snapshot of the variant price at the moment it was added/refreshed.
            // This is a *display* convenience only — CheckoutService always
            // re-reads the live variant price before creating the order and
            // will reject the checkout (not silently repair it) if it has drifted.
            $table->integer('unit_price_cents');
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('carts')->cascadeOnDelete();

            // One line per variant per cart — adding an already-present variant
            // increments quantity instead of creating a second row.
            $table->unique(['cart_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};