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
        Schema::create('orders', function (Blueprint $table) {
            // Must use ulid() here so order_items and payments foreign keys match!
            $table->ulid('id')->primary();

            // Multi-Tenancy & Customer Reference (assumes ULIDs or BigInt depending on your store table setup)
            $table->foreignUlid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->nullOnDelete();

            // Human-Readable Identifiers
            $table->string('order_number')->unique();

            // Statuses
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('fulfillment_status')->default('unfulfilled');

            // Financials (matching Money VO in minor units)
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal_cents');
            $table->integer('tax_cents')->default(0);
            $table->integer('shipping_cents')->default(0);
            $table->integer('discount_cents')->default(0);
            $table->integer('total_cents');

            // Snapshots
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->jsonb('shipping_address')->nullable();
            $table->jsonb('billing_address')->nullable();

            // Gateway & Metadata
            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
