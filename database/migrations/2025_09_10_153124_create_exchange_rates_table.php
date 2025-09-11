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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            
            // Información de la tasa
            $table->string('currency_code', 10); // USD, EUR, etc.
            $table->decimal('rate', 15, 8); // Tasa de cambio (hasta 8 decimales)
            $table->string('source', 20); // BCV, BINANCE, etc.
            $table->string('target_currency', 10)->default('VES'); // Moneda objetivo (VES por defecto)
            
            // Campos para cálculos de precios de planes
            $table->decimal('binance_equivalent', 15, 8)->nullable(); // Equivalente en Binance (para cálculos)
            $table->decimal('bcv_equivalent', 15, 8)->nullable(); // Equivalente en BCV (para cálculos)
            $table->decimal('conversion_factor', 10, 6)->nullable(); // Factor de conversión entre tasas
            
            // Metadatos
            $table->timestamp('fetched_at'); // Cuándo se obtuvo la tasa
            $table->boolean('is_valid')->default(true); // Si la tasa es válida
            $table->text('error_message')->nullable(); // Mensaje de error si falló
            $table->json('metadata')->nullable(); // Datos adicionales (volumen, etc.)
            
            // Timestamps
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['currency_code', 'source', 'fetched_at']);
            $table->index(['source', 'is_valid', 'fetched_at']);
            $table->index(['currency_code', 'is_valid']);
            
            // Índice único para evitar duplicados (misma moneda, fuente y tiempo)
            $table->unique(['currency_code', 'source', 'fetched_at'], 'unique_rate_per_source_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};