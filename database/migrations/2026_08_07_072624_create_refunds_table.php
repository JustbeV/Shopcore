<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('store_id')->index();
            $table->ulid('order_id')->index();
            $table->ulid('payment_id');
            $table->ulid('requested_by_customer_id')->nullable();
            $table->enum('status', ['requested', 'approved', 'rejected', 'processed'])->default('requested');
            $table->integer('amount_cents');
            $table->text('reason')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // MySQL Workaround: Virtual column that yields order_id only when requested
            $table->rawColumn(
                'open_requested_order_id',
                'CHAR(26) GENERATED ALWAYS AS (IF(status = "requested", order_id, NULL)) VIRTUAL'
            );

            // Foreign keys
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $table->foreign('requested_by_customer_id')->references('id')->on('customers')->nullOnDelete();

            // Unique index on the generated column
            $table->unique('open_requested_order_id', 'refunds_order_open_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};