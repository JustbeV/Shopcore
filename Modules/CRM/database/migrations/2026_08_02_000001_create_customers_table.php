<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('store_id')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            // Nullable: guest-checkout customers (see Sales\CheckoutController)
            // are created without a password and can "claim" the account
            // later via registration with the same email — see
            // CustomerAuthService::register()'s claim-existing-guest path.
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->jsonb('default_address')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();

            // Uniqueness is per-store, not global — the same email can be a
            // customer at two different stores (§5.1: "a customer account
            // is scoped to a store, mirroring Shopify's model").
            $table->unique(['store_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
