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
        Schema::create('campaign_plan_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('active_campaign_id')->constrained('active_campaigns_view')->onDelete('cascade');
            $table->foreignId('advertising_plan_id')->nullable()->constrained('advertising_plans')->onDelete('set null');
            $table->enum('reconciliation_status', ['pending', 'approved', 'rejected', 'completed', 'paused'])->default('pending');
            $table->timestamp('reconciliation_date')->nullable();
            $table->text('notes')->nullable();
            
            // Datos de conciliación contable
            $table->decimal('planned_budget', 10, 2)->nullable(); // Presupuesto planificado del plan
            $table->decimal('actual_spent', 10, 2)->nullable(); // Gasto real de la campaña
            $table->decimal('variance', 10, 2)->nullable(); // Diferencia (planned - actual)
            $table->decimal('variance_percentage', 8, 2)->nullable(); // Porcentaje de variación
            
            // Metadatos
            $table->json('reconciliation_data')->nullable(); // Datos adicionales de la conciliación
            $table->timestamp('last_updated_at')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['active_campaign_id', 'advertising_plan_id']);
            $table->index('reconciliation_status');
            $table->index('reconciliation_date');
            $table->index('advertising_plan_id'); // Índice separado para consultas con/sin plan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_plan_reconciliations');
    }
};