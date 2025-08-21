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
        Schema::create('report_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('report_brand_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('campaign_id', 255); // ID de la campaña de Facebook
            $table->string('campaign_name', 500); // Nombre de la campaña (aumentado)
            $table->string('ad_account_id', 255); // ID de la cuenta publicitaria
            $table->json('campaign_data')->nullable(); // Datos completos de la campaña
            $table->json('statistics')->nullable(); // Estadísticas de la campaña
            $table->text('ad_image_url')->nullable(); // URL de la imagen del anuncio (cambiado a text)
            $table->text('ad_image_local_path')->nullable(); // Ruta local de la imagen (cambiado a text)
            $table->integer('slide_order')->default(0); // Orden en la diapositiva
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_campaigns');
    }
};
