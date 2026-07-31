<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Belongs to parent Product
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();

            // Unique Stock Keeping Unit
            $table->string('sku')->nullable()->unique();

            // Financials (matching Money VO in minor units / cents)
            $table->integer('price_cents');
            $table->integer('compare_at_price_cents')->nullable(); // Original price before sale

            // Inventory Tracking
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);

            // Flexible Option Snapshot (e.g., {"color": "Red", "size": "XL"})
            $table->jsonb('options')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
