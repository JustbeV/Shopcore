<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            // Not in the original §7.4 ERD — added because the storefront
            // needs to re-fetch the same client_secret on an idempotent
            // retry (e.g. page refresh mid-payment) without calling Stripe
            // again. Short-lived and low-sensitivity (useless without the
            // customer's own payment method), so plain text is fine here.
            $table->string('client_secret')->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
