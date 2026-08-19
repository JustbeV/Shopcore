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
        Schema::create('stores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('owner_id')->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->unique();
            $table->enum('status', ['pending_setup', 'active', 'suspended', 'closed'])->default('pending_setup');
            $table->boolean('is_published')->default(false);
            $table->enum('isolation_mode', ['shared', 'dedicated'])->default('shared');
            $table->jsonb('settings')->default('{}');
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
 
            $table->foreign('owner_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
