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
        Schema::create('store_staff', function (Blueprint $table): void {
            $table->ulid('id')->primary();
 
            $table->ulid('store_id'); // FK to stores — see note above
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();
 
            $table->string('status')->default('invited'); // invited | active | revoked
 
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
 
            $table->timestamps();
 
            $table->index(['store_id', 'status']);
            // A user can only have one staff membership per store —
            // re-inviting after revocation updates the existing row
            // rather than creating a duplicate.
            $table->unique(['store_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_staff');
    }
};
