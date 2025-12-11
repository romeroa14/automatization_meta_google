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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('user_id')->nullable();
            $table->string('number_phone_id')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->text('message_text')->nullable();
            $table->text('response')->nullable();
            $table->string('resource')->nullable(); // URL or path
            $table->string('timestamp')->nullable();
            $table->string('platform')->nullable();
            $table->string('status')->nullable();
            $table->integer('message_length')->nullable();
            $table->boolean('is_employee')->default(false);
            $table->boolean('is_client_message')->default(true);
            $table->string('lead_intent')->nullable();
            $table->string('lead_level')->nullable();
            $table->text('conversation_summary')->nullable();
            $table->string('message_sentiment')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
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
