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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('store_id')->index();
            $table->string('name'); // e.g. "Standard Shipping", "Express"
            // ISO 3166-1 alpha-2, or 'ALL' to match any country. No
            // zones/carriers table — a flat per-country rate list is what
            // "shipping rates" needs for Phase 6; carrier integration
            // (live rate quotes from UPS/USPS/etc.) is a real future build,
            // not implied by this phase.
            $table->string('country_code', 3)->default('ALL');
            $table->integer('price_cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
 
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->index(['store_id', 'country_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
