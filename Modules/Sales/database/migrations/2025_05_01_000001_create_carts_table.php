<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('store_id')->index();
            // Nullable: guest carts are allowed and are only ever attached
            // to a user once they authenticate (see CartService::mergeIntoCustomer()).
            $table->ulid('customer_id')->nullable()->index();
            // Opaque, client-held token used to resolve *guest* carts.
            // Never used once a cart has a customer_id.
            $table->string('session_token')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('users')->nullOnDelete();

            // A guest cart is looked up by (store_id, session_token); a customer
            // cart is looked up by (store_id, customer_id). Both need to be fast
            // and both are always filtered by store_id first (tenant scope).
            $table->unique(['store_id', 'session_token']);
            $table->unique(['store_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};