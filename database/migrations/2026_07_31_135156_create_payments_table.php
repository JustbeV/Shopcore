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
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('store_id')->index();
            $table->ulid('order_id')->index();
            $table->enum('provider', ['stripe', 'paypal', 'wallet']);
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            // Provider's payment intent / order id. Unique per provider so we
            // can look a payment up directly from a webhook payload.
            $table->string('provider_reference')->nullable();
            $table->integer('amount_cents');
            $table->string('currency', 3);
            $table->jsonb('failure_reason')->nullable();
            $table->timestamps();
 
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
 
            $table->unique(['provider', 'provider_reference']);
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
