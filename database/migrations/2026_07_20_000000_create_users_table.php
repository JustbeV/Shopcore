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
        Schema::create('users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
 
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
 
            // Fortify's standard 2FA columns (architecture §6.2: TOTP +
            // recovery codes). Encrypted casts applied at the model
            // level, not here.
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
 
            // Platform-level lifecycle, independent of any store —
            // e.g. a merchant owner's account is suspended, which is a
            // different concern from their store being suspended.
            $table->string('status')->default('pending_verification');
 
            $table->rememberToken();
            $table->timestamps();
 
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
