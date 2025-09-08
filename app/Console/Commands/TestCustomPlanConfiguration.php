<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountingTransaction;
use App\Models\CampaignPlanReconciliation;
use App\Services\CampaignReconciliationService;

class TestCustomPlanConfiguration extends Command
{
    protected $signature = 'test:custom-plan-configuration';
    protected $description = 'Probar la configuración de planes personalizados con detección automática de Instagram y fechas';

    public function handle()
    {
        $this->info('🧪 Probando configuración de planes personalizados...');
        
        // Buscar transacciones de planes personalizados
        $customTransactions = AccountingTransaction::whereHas('campaignReconciliation.advertisingPlan', function($query) {
            $query->where('plan_name', 'like', '%Plan Personalizado%');
        })->get();
        
        if ($customTransactions->isEmpty()) {
            $this->warn('No se encontraron transacciones de planes personalizados.');
            return;
        }
        
        $this->info("📊 Encontradas {$customTransactions->count()} transacciones de planes personalizados:");
        
        foreach ($customTransactions as $transaction) {
            $this->line("---");
            $this->info("🆔 Transacción ID: {$transaction->id}");
            $this->info("👤 Cliente: {$transaction->client_name}");
            $this->info("📅 Inicio: " . ($transaction->campaign_start_date ?? 'NULL'));
            $this->info("📅 Final: " . ($transaction->campaign_end_date ?? 'NULL'));
            $this->info("💰 Ingreso: $" . number_format($transaction->income, 2));
            $this->info("💸 Gasto: $" . number_format($transaction->expense, 2));
            $this->info("💵 Ganancia: $" . number_format($transaction->profit, 2));
            $this->info("📊 Estado: {$transaction->status}");
            
            // Verificar si tiene datos completos
            $hasCompleteData = $transaction->client_name && 
                              $transaction->campaign_start_date && 
                              $transaction->campaign_end_date;
            
            if ($hasCompleteData) {
                $this->info("✅ Datos completos: Instagram detectado y fechas extraídas");
            } else {
                $this->warn("⚠️  Datos incompletos: Faltan Instagram o fechas");
                
                // Intentar corregir automáticamente
                $this->info("🔧 Intentando corregir automáticamente...");
                
                $reconciliation = $transaction->campaignReconciliation;
                if ($reconciliation && $reconciliation->activeCampaign) {
                    $activeCampaign = $reconciliation->activeCampaign;
                    $reconciliationService = new CampaignReconciliationService();
                    
                    // Detectar Instagram
                    $instagramClientName = $reconciliationService->extractClientName($activeCampaign);
                    
                    // Obtener fechas
                    $campaignStartDate = $activeCampaign->campaign_start_time?->format('Y-m-d');
                    $campaignEndDate = $activeCampaign->campaign_stop_time?->format('Y-m-d');
                    
                    // Actualizar transacción
                    $transaction->update([
                        'client_name' => $instagramClientName,
                        'campaign_start_date' => $campaignStartDate,
                        'campaign_end_date' => $campaignEndDate,
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'instagram_detected' => $instagramClientName !== 'Cliente Sin Identificar',
                            'campaign_dates' => [
                                'start_date' => $campaignStartDate,
                                'end_date' => $campaignEndDate,
                                'duration_days' => $activeCampaign->getCampaignDurationDays()
                            ],
                            'auto_corrected_at' => now()->toISOString()
                        ])
                    ]);
                    
                    $this->info("✅ Corregido automáticamente:");
                    $this->info("   👤 Cliente: {$instagramClientName}");
                    $this->info("   📅 Inicio: " . ($campaignStartDate ?? 'NULL'));
                    $this->info("   📅 Final: " . ($campaignEndDate ?? 'NULL'));
                }
            }
        }
        
        $this->info("🎉 Prueba completada!");
    }
}
