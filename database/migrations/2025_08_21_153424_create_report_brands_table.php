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
        Schema::create('report_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->string('brand_name'); // Nombre de la marca
            $table->string('brand_identifier')->nullable(); // Identificador único de la marca
            $table->json('campaign_ids')->nullable(); // IDs de campañas asociadas a esta marca
            $table->json('brand_settings')->nullable(); // Configuraciones específicas de la marca
            $table->integer('slide_order')->default(0); // Orden de la marca en las diapositivas
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_brands');
    }
};
