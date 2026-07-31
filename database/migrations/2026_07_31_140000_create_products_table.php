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
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Multi-Tenancy Scope (Phase 0 / Phase 2)
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();

            // Product Details
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            
            // Status: draft, active, archived
            $table->string('status')->default('draft');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for storefront lookups & multi-tenant isolation
            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
