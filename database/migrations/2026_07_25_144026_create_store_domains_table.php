<?php

declare(strict_types=1);

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
        Schema::create('store_domains', function (Blueprint $table): void {
            $table->ulid('id')->primary();
 
            $table->foreignUlid('store_id')->constrained('stores')->cascadeOnDelete();
 
            $table->string('hostname')->unique();
            $table->boolean('is_primary')->default(false);
 
            $table->string('verification_status')->default('pending'); // pending|verified|failed
            $table->string('verification_token', 64);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
 
            $table->timestamps();
 
            $table->index(['store_id', 'verification_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_domains');
    }
};
