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
        Schema::create('analysis_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->json('input_data'); // Datos de entrada (métricas de Facebook)
            $table->json('analysis_result'); // Resultado del análisis de IA
            $table->json('performance_metrics'); // Métricas calculadas
            $table->text('prompt_used'); // Prompt utilizado
            $table->string('model_version')->default('gemini-1.5-flash');
            $table->integer('tokens_used')->nullable();
            $table->float('processing_time')->nullable(); // Tiempo de procesamiento en segundos
            $table->json('feedback_data')->nullable(); // Datos de retroalimentación
            $table->boolean('was_helpful')->nullable(); // Si el análisis fue útil
            $table->text('user_notes')->nullable(); // Notas del usuario
            $table->timestamps();
            
            // Índices para búsquedas eficientes
            $table->index(['report_id', 'created_at']);
            $table->index('model_version');
            $table->index('was_helpful');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_histories');
    }
};
