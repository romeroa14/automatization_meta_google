<?php

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
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->string('sender_type', 50); // 'customer', 'bot', 'agent'
            $table->text('content');
            $table->string('message_type', 50)->default('text'); // 'text', 'image', 'video', 'audio', 'document', 'location', 'contact'
            $table->json('metadata')->nullable();
            $table->string('message_id', 255)->nullable(); // Platform message ID
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('sender_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};