<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversations') && !Schema::hasTable('legacy_conversations')) {
            Schema::rename('conversations', 'legacy_conversations');
        }
        Schema::dropIfExists('messages');
        
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('messages');
    }
};
