<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_lead_id')->constrained('tenant_leads')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_instance_id')->nullable()->constrained('whatsapp_instances')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Agente respondiente
            $table->string('message_id')->nullable(); // wamid
            $table->enum('direction', ['inbound', 'outbound']); 
            $table->boolean('is_client_message')->default(true); // Compatibilidad front
            $table->boolean('is_employee')->default(false); // Compatibilidad front
            $table->text('content');
            $table->string('platform')->default('whatsapp');
            $table->string('status')->default('sent');
            $table->integer('message_length')->default(0);
            $table->boolean('handled_by_ai')->default(false);
            $table->timestamp('timestamp')->nullable(); // Meta API timestamp
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_messages');
    }
};
