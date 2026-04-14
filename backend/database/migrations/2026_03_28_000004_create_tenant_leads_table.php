<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->nullable()->constrained('whatsapp_instances')->nullOnDelete();
            $table->string('phone_number')->unique('tenant_leads_phone_unique');
            $table->string('client_name')->nullable();
            $table->string('intent')->nullable();
            $table->string('lead_level')->nullable();
            $table->string('stage')->default('new');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->boolean('bot_disabled')->default(false);
            $table->timestamp('last_human_intervention_at')->nullable();
            $table->jsonb('ai_classification')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_leads');
    }
};
