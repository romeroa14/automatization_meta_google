<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MicroscopicAccountingService;
use App\Models\ActiveCampaign;

class TestMicroscopicAccounting extends Command
{
    protected $signature = 'test:microscopic-accounting';
    protected $description = 'Probar el sistema de contabilidad microscópica basado en estados de campañas';

    public function handle()
    {
        $this->info('🔬 Iniciando prueba de contabilidad microscópica...');
        
        // Primero, mostrar el estado actual de las campañas
        $this->showCurrentCampaignStatuses();
        
        $this->line('');
        $this->info('🚀 Ejecutando procesamiento microscópico...');
        
        // Ejecutar el servicio de contabilidad microscópica
        $microscopicService = new MicroscopicAccountingService();
        $results = $microscopicService->processCampaignsByStatus();
        
        // Mostrar resultados
        $this->displayResults($results);
        
        $this->info('🎉 Prueba de contabilidad microscópica completada!');
    }
    
    private function showCurrentCampaignStatuses()
    {
        $this->info('📊 Estado actual de las campañas:');
        
        $campaigns = ActiveCampaign::all();
        
        if ($campaigns->isEmpty()) {
            $this->warn('No se encontraron campañas activas.');
            return;
        }
        
        $this->table(
            ['Campaña', 'Estado Meta', 'Estado Real', 'Presupuesto Diario', 'Gastado', 'Duración'],
            $campaigns->map(function ($campaign) {
                $realStatus = $campaign->getRealCampaignStatus();
                $dailyBudget = $campaign->convertMetaNumber($campaign->campaign_daily_budget ?? $campaign->adset_daily_budget ?? 0, 'budget');
                $actualSpent = $campaign->convertMetaNumber($campaign->getAmountSpentFromMeta() ?? $campaign->getAmountSpentEstimated() ?? 0, 'amount');
                $duration = $campaign->getCampaignDurationDays() ?? 'N/A';
                
                return [
                    substr($campaign->meta_campaign_name, 0, 30) . '...',
                    $campaign->campaign_status ?? 'N/A',
                    $realStatus,
                    '$' . number_format($dailyBudget, 2),
                    '$' . number_format($actualSpent, 2),
                    $duration . ' días'
                ];
            })
        );
    }
    
    private function displayResults(array $results)
    {
        $this->line('');
        $this->info('📈 Resultados del procesamiento microscópico:');
        
        // Mostrar resumen general
        if (isset($results['summary'])) {
            $summary = $results['summary'];
            $this->info("📊 Resumen General:");
            $this->line("   • Total procesadas: {$summary['total_campaigns_processed']}");
            $this->line("   • Total conciliadas: {$summary['total_campaigns_reconciled']}");
            $this->line("   • Total errores: {$summary['total_errors']}");
            $this->line("   • Tasa de éxito: " . number_format($summary['success_rate'], 2) . "%");
            
            $this->line('');
            $this->info("📋 Desglose por estado:");
            foreach ($summary['status_breakdown'] as $status => $count) {
                $emoji = match($status) {
                    'active' => '🟢',
                    'paused' => '🔴',
                    'scheduled' => '🔵',
                    'completed' => '✅',
                    default => '❓'
                };
                $this->line("   {$emoji} {$status}: {$count} campañas");
            }
        }
        
        // Mostrar detalles por estado
        foreach (['active', 'paused', 'scheduled', 'completed'] as $status) {
            if (isset($results[$status]) && $results[$status]['processed'] > 0) {
                $this->line('');
                $emoji = match($status) {
                    'active' => '🟢',
                    'paused' => '🔴',
                    'scheduled' => '🔵',
                    'completed' => '✅',
                    default => '❓'
                };
                
                $this->info("{$emoji} Estado: " . strtoupper($status));
                $this->line("   • Procesadas: {$results[$status]['processed']}");
                $this->line("   • Conciliadas: {$results[$status]['reconciled']}");
                
                if (!empty($results[$status]['errors'])) {
                    $this->line("   • Errores: " . count($results[$status]['errors']));
                    foreach ($results[$status]['errors'] as $error) {
                        $this->error("     - {$error}");
                    }
                }
            }
        }
        
        // Mostrar recomendaciones
        $this->line('');
        $this->info('💡 Recomendaciones:');
        
        if (isset($results['paused']) && $results['paused']['processed'] > 0) {
            $this->line('   🔴 Campañas PAUSADAS: Se registró solo el gasto real (sin conciliar plan completo)');
        }
        
        if (isset($results['scheduled']) && $results['scheduled']['processed'] > 0) {
            $this->line('   🔵 Campañas PROGRAMADAS: No se crearon conciliaciones (esperando activación)');
        }
        
        if (isset($results['active']) && $results['active']['processed'] > 0) {
            $this->line('   🟢 Campañas ACTIVAS: Conciliación normal con plan completo');
        }
        
        if (isset($results['completed']) && $results['completed']['processed'] > 0) {
            $this->line('   ✅ Campañas COMPLETADAS: Conciliación final realizada');
        }
    }
}
