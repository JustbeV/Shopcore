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
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
 
            // Nullable: some audit entries originate from system/queue
            // actions with no acting user (e.g. an automated
            // subscription downgrade), not just admin/merchant actions.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
 
            $table->string('action');
 
            // Polymorphic target of the action (MerchantApplication,
            // Store, Order, ...). Using string+ulid rather than
            // Eloquent's morphs() ulid helper directly for explicit
            // column naming that matches the ERD.
            $table->string('auditable_type');
            $table->ulid('auditable_id');
 
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
 
            $table->string('ip_address', 45)->nullable();
 
            $table->timestamp('created_at')->useCurrent();
 
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
