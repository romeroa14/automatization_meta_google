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
        Schema::create('campaign_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facebook_account_id'); // Cuenta de Facebook
            $table->unsignedBigInteger('advertising_plan_id')->nullable(); // Plan de publicidad detectado
            $table->string('meta_campaign_id'); // ID de la campaña en Meta
            $table->string('meta_campaign_name'); // Nombre de la campaña en Meta
            $table->string('meta_adset_id')->nullable(); // ID del conjunto de anuncios
            $table->string('meta_ad_id')->nullable(); // ID del anuncio específico
            $table->string('client_name')->nullable(); // Nombre del cliente (fanpage/instagram)
            $table->string('client_type')->default('fanpage'); // Tipo: fanpage, instagram, ambos
            $table->decimal('daily_budget', 10, 2); // Presupuesto diario detectado
            $table->integer('duration_days')->nullable(); // Duración estimada en días
            $table->decimal('total_budget', 10, 2); // Presupuesto total estimado
            $table->decimal('client_price', 10, 2)->nullable(); // Precio al cliente (del plan)
            $table->decimal('profit_margin', 10, 2)->nullable(); // Ganancia esperada
            $table->decimal('actual_spend', 10, 2)->default(0); // Gasto real en Meta
            $table->decimal('remaining_budget', 10, 2)->nullable(); // Presupuesto restante
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->date('campaign_start_date')->nullable(); // Fecha de inicio
            $table->date('campaign_end_date')->nullable(); // Fecha de finalización
            $table->json('meta_data')->nullable(); // Datos adicionales de Meta
            $table->text('notes')->nullable(); // Notas adicionales
            $table->timestamps();
            
            // Relaciones
            $table->foreign('facebook_account_id')->references('id')->on('facebook_accounts')->onDelete('cascade');
            $table->foreign('advertising_plan_id')->references('id')->on('advertising_plans')->onDelete('set null');
            
            // Índices para optimización
            $table->index(['meta_campaign_id', 'status']);
            $table->index(['facebook_account_id', 'status']);
            $table->index(['client_name', 'status']);
            $table->index(['campaign_start_date', 'campaign_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_reconciliations');
    }
};
