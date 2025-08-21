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
            $table->string('campaign_id'); // ID de la campaña de Facebook
            $table->string('campaign_name'); // Nombre de la campaña
            $table->string('ad_account_id'); // ID de la cuenta publicitaria
            $table->json('campaign_data')->nullable(); // Datos completos de la campaña
            $table->json('statistics')->nullable(); // Estadísticas de la campaña
            $table->string('ad_image_url')->nullable(); // URL de la imagen del anuncio
            $table->string('ad_image_local_path')->nullable(); // Ruta local de la imagen
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
