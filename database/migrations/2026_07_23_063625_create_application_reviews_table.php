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
        Schema::create('application_reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
 
            $table->foreignUlid('application_id')->constrained('merchant_applications')->cascadeOnDelete();
            $table->foreignUlid('reviewer_id')->constrained('users')->cascadeOnDelete();
 
            $table->string('action'); // approve | reject | request_info
            $table->text('notes')->nullable();
 
            // ERD (§7.2) specifies created_at only — a review is an
            // immutable event record, never edited after the fact.
            $table->timestamp('created_at')->useCurrent();
 
            $table->index(['application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_reviews');
    }
};
