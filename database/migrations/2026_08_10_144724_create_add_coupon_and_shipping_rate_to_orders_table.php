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
        Schema::table('orders', function (Blueprint $table) {
            $table->ulid('coupon_id')->nullable()->after('discount_cents');
            // Snapshot — coupons can be edited/deleted later, the order
            // should still show what code was actually used.
            $table->string('coupon_code_snapshot')->nullable()->after('coupon_id');
            $table->ulid('shipping_rate_id')->nullable()->after('shipping_cents');
            // No FK constraint on purpose — shipping_rates lives in the
            // Shipping module, and a cross-module FK would couple this
            // migration's run order to that module's migration timestamp.
            // Referential integrity here is enforced at the application
            // layer (CheckoutService validates the rate exists before use).
            $table->string('shipping_rate_name_snapshot')->nullable()->after('shipping_rate_id');
 
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_code_snapshot', 'shipping_rate_id', 'shipping_rate_name_snapshot']);
        });
    }
};
