<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Recreates the conversations table with multi-tenant schema.
     * Table was dropped by 2026_04_13_235309_drop_legacy_leads_and_conversations_tables
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('platform', 50); // 'instagram', 'whatsapp', 'telegram'
            $table->string('customer_id', 255); // Platform user ID
            $table->string('customer_name', 255)->nullable();
            $table->string('status', 50)->default('active'); // 'active', 'closed'
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Foreign key to tenants
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Composite unique index: one conversation per customer per platform per tenant
            $table->unique(['tenant_id', 'platform', 'customer_id'], 'conversations_tenant_platform_customer_unique');

            // Indexes for common queries
            $table->index('tenant_id');
            $table->index('status');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};