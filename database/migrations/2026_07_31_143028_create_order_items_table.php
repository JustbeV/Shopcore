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
        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id')->index();
            $table->ulid('variant_id')->nullable()->index();
            // Snapshots, deliberately duplicated from the product/variant at
            // time of purchase — orders must stay accurate even if the
            // merchant later renames/reprices/deletes the product.
            $table->string('product_title_snapshot');
            $table->string('sku_snapshot')->nullable();
            $table->jsonb('options_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->integer('unit_price_cents');
            $table->integer('total_cents');
            $table->timestamps();
 
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            // Intentionally NOT cascading/restricting on variant_id deletion
            // in a way that would block deleting a variant — the snapshot
            // columns above are what orders actually depend on.
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
