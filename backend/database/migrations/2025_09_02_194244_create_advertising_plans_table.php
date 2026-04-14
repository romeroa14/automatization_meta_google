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
        Schema::create('advertising_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name'); // Nombre del plan (ej: "Plan Básico 7 días")
            $table->text('description')->nullable(); // Descripción del plan
            $table->decimal('daily_budget', 10, 2); // Presupuesto diario (ej: 3.00)
            $table->integer('duration_days'); // Duración en días (ej: 7)
            $table->decimal('total_budget', 10, 2); // Presupuesto total (ej: 21.00)
            $table->decimal('client_price', 10, 2); // Precio al cliente (ej: 29.00)
            $table->decimal('profit_margin', 10, 2); // Ganancia (ej: 8.00)
            $table->decimal('profit_percentage', 5, 2); // Porcentaje de ganancia
            $table->boolean('is_active')->default(true); // Si el plan está activo
            $table->json('features')->nullable(); // Características del plan
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['is_active', 'daily_budget']);
            $table->index(['total_budget', 'client_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertising_plans');
    }
};
