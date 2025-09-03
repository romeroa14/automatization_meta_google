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
        Schema::create('active_campaigns_view', function (Blueprint $table) {
            $table->id();
            
            // Niveles jerárquicos de Meta Ads
            $table->string('meta_campaign_id'); // ID de la campaña
            $table->string('meta_adset_id')->nullable(); // ID del conjunto de anuncios
            $table->string('meta_ad_id')->nullable(); // ID del anuncio individual
            
            // Información básica
            $table->string('meta_campaign_name');
            $table->string('meta_adset_name')->nullable();
            $table->string('meta_ad_name')->nullable();
            
            // Presupuestos por nivel
            $table->decimal('campaign_daily_budget', 10, 2)->nullable(); // Presupuesto diario de campaña
            $table->decimal('campaign_total_budget', 10, 2)->nullable(); // Presupuesto total de campaña
            $table->decimal('adset_daily_budget', 10, 2)->nullable(); // Presupuesto diario del adset
            $table->decimal('adset_lifetime_budget', 10, 2)->nullable(); // Presupuesto total del adset
            
            // Fechas y duración
            $table->dateTime('campaign_start_time')->nullable();
            $table->dateTime('campaign_stop_time')->nullable();
            $table->dateTime('adset_start_time')->nullable();
            $table->dateTime('adset_stop_time')->nullable();
            
            // Estado y objetivo
            $table->string('campaign_status');
            $table->string('adset_status')->nullable();
            $table->string('ad_status')->nullable();
            $table->string('campaign_objective')->nullable();
            
            // Relaciones
            $table->unsignedBigInteger('facebook_account_id');
            $table->string('ad_account_id');
            
            // Datos JSON completos por nivel
            $table->json('campaign_data')->nullable(); // Todos los datos de la campaña
            $table->json('adset_data')->nullable(); // Todos los datos del adset
            $table->json('ad_data')->nullable(); // Todos los datos del anuncio
            
            // Timestamps
            $table->timestamps();
            
            // Relaciones
            $table->foreign('facebook_account_id')->references('id')->on('facebook_accounts')->onDelete('cascade');
            
            // Índices para optimización
            $table->index(['meta_campaign_id', 'meta_adset_id', 'meta_ad_id']);
            $table->index(['facebook_account_id', 'ad_account_id']);
            $table->index(['campaign_status', 'adset_status', 'ad_status']);
            $table->index(['campaign_start_time', 'campaign_stop_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_campaigns_view');
    }
};
