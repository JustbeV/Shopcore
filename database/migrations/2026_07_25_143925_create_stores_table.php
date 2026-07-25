<?php

declare(strict_types=1);

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
        Schema::create('stores', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->unique();

            $table->string('status')->default('pending_setup'); // pending_setup|active|suspended|closed
            $table->boolean('is_published')->default(false);

            $table->string('isolation_mode')->default('shared'); // shared|dedicated

            // FIX: Use json() instead of jsonb(), and use DB::raw for the default JSON object
            $table->json('settings')->default(DB::raw('(json_object())'));

            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['status', 'is_published']);
        });

        // Postgres & MySQL 8.0+ constraint syntax
        DB::statement(<<<'SQL'
            ALTER TABLE stores
            ADD CONSTRAINT stores_publish_requires_active_status
            CHECK (is_published = 0 OR status = 'active')
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};