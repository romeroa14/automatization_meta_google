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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del reporte
            $table->text('description')->nullable(); // Descripción del reporte
            $table->date('period_start'); // Fecha de inicio del período
            $table->date('period_end'); // Fecha de fin del período
            $table->json('selected_facebook_accounts')->nullable(); // IDs de cuentas de Facebook seleccionadas
            $table->json('selected_campaigns')->nullable(); // IDs de campañas específicas
            $table->json('brands_config')->nullable(); // Configuración de marcas y sus campañas
            $table->json('statistics_config')->nullable(); // Configuración de estadísticas a incluir
            $table->json('charts_config')->nullable(); // Configuración de gráficas
            $table->json('generated_data')->nullable(); // Datos generados del reporte
            $table->string('google_slides_url')->nullable(); // URL de la presentación generada
            $table->string('status')->default('draft'); // draft, generating, completed, failed
            $table->timestamp('generated_at')->nullable(); // Cuándo se generó el reporte
            $table->json('settings')->nullable(); // Configuraciones adicionales
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
