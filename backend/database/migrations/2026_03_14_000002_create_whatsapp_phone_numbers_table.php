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
        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('phone_number')->unique(); // Número de teléfono en formato E.164
            $table->string('display_name')->nullable(); // Nombre para mostrar
            $table->string('phone_number_id'); // ID del número en Meta/WhatsApp
            $table->string('waba_id'); // WhatsApp Business Account ID
            $table->string('access_token', 500); // Token de acceso (encriptado)
            $table->string('verify_token')->nullable(); // Token de verificación para webhooks
            $table->string('webhook_url')->nullable(); // URL del webhook
            $table->string('status')->default('pending'); // pending, active, suspended, inactive
            $table->string('quality_rating')->nullable(); // green, yellow, red
            $table->json('capabilities')->nullable(); // voice, video, document, etc.
            $table->json('settings')->nullable(); // Configuraciones adicionales
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_default')->default(false); // Número por defecto de la organización
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('organization_id');
            $table->index('phone_number_id');
            $table->index('status');
        });

        // Agregar columna organization_id a la tabla leads
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_phone_number_id')->nullable()->after('organization_id')->constrained()->onDelete('set null');
        });

        // Agregar columna organization_id a la tabla conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_phone_number_id')->nullable()->after('organization_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['whatsapp_phone_number_id']);
            $table->dropColumn(['organization_id', 'whatsapp_phone_number_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['whatsapp_phone_number_id']);
            $table->dropColumn(['organization_id', 'whatsapp_phone_number_id']);
        });

        Schema::dropIfExists('whatsapp_phone_numbers');
    }
};
