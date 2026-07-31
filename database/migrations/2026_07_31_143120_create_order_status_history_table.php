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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            // Nullable: system-driven transitions (e.g. webhook confirming
            // payment) have no acting user.
            $table->ulid('changed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at');
 
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
