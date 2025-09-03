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
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_reconciliation_id')->nullable(); // Relación con conciliación
            $table->unsignedBigInteger('advertising_plan_id')->nullable(); // Plan de publicidad
            $table->string('transaction_type'); // Tipo: income, expense, profit, refund
            $table->string('description'); // Descripción de la transacción
            $table->decimal('amount', 10, 2); // Monto de la transacción
            $table->string('currency')->default('USD'); // Moneda
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->string('reference_number')->nullable(); // Número de referencia
            $table->string('client_name')->nullable(); // Nombre del cliente
            $table->string('meta_campaign_id')->nullable(); // ID de campaña en Meta
            $table->date('transaction_date'); // Fecha de la transacción
            $table->date('due_date')->nullable(); // Fecha de vencimiento
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->text('notes')->nullable(); // Notas adicionales
            $table->timestamps();
            
            // Relaciones
            $table->foreign('campaign_reconciliation_id')->references('id')->on('campaign_reconciliations')->onDelete('set null');
            $table->foreign('advertising_plan_id')->references('id')->on('advertising_plans')->onDelete('set null');
            
            // Índices para optimización
            $table->index(['transaction_type', 'status']);
            $table->index(['client_name', 'transaction_date']);
            $table->index(['meta_campaign_id', 'status']);
            $table->index(['transaction_date', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_transactions');
    }
};
