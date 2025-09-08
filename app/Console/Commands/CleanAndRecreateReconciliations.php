<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountingTransaction;
use App\Models\CampaignPlanReconciliation;
use App\Services\MicroscopicAccountingService;

class CleanAndRecreateReconciliations extends Command
{
    protected $signature = 'clean:recreate-reconciliations {--force : Forzar la limpieza sin confirmación}';
    protected $description = 'Limpiar conciliaciones existentes y recrearlas con el sistema de contabilidad microscópica';

    public function handle()
    {
        $this->info('🧹 Iniciando limpieza y recreación de conciliaciones...');
        
        // Mostrar estado actual
        $this->showCurrentState();
        
        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que quieres limpiar todas las conciliaciones existentes?')) {
                $this->info('Operación cancelada.');
                return;
            }
        }
        
        // Limpiar conciliaciones existentes
        $this->cleanExistingReconciliations();
        
        // Recrear con sistema microscópico
        $this->recreateWithMicroscopicSystem();
        
        $this->info('✅ Limpieza y recreación completada!');
    }
    
    private function showCurrentState()
    {
        $this->info('📊 Estado actual:');
        
        $transactions = AccountingTransaction::count();
        $reconciliations = CampaignPlanReconciliation::count();
        $campaigns = \App\Models\ActiveCampaign::count();
        
        $this->line("   • Transacciones contables: {$transactions}");
        $this->line("   • Conciliaciones: {$reconciliations}");
        $this->line("   • Campañas activas: {$campaigns}");
        
        // Mostrar detalles de campañas
        $this->line('');
        $this->info('🎯 Estado de campañas:');
        $campaigns = \App\Models\ActiveCampaign::all();
        foreach ($campaigns as $campaign) {
            $realStatus = $campaign->getRealCampaignStatus();
            $emoji = match(strtolower($realStatus)) {
                'active' => '🟢',
                'paused' => '🔴',
                'scheduled' => '🔵',
                'completed' => '✅',
                default => '❓'
            };
            $this->line("   {$emoji} {$campaign->meta_campaign_name} - {$realStatus}");
        }
    }
    
    private function cleanExistingReconciliations()
    {
        $this->info('🧹 Limpiando conciliaciones existentes...');
        
        // Eliminar transacciones contables
        $transactionsDeleted = AccountingTransaction::count();
        AccountingTransaction::query()->delete();
        $this->line("   • Eliminadas {$transactionsDeleted} transacciones contables");
        
        // Eliminar conciliaciones
        $reconciliationsDeleted = CampaignPlanReconciliation::count();
        CampaignPlanReconciliation::query()->delete();
        $this->line("   • Eliminadas {$reconciliationsDeleted} conciliaciones");
        
        $this->info('✅ Limpieza completada');
    }
    
    private function recreateWithMicroscopicSystem()
    {
        $this->info('🔬 Recreando con sistema de contabilidad microscópica...');
        
        $microscopicService = new MicroscopicAccountingService();
        $results = $microscopicService->processCampaignsByStatus();
        
        // Mostrar resultados
        $summary = $results['summary'] ?? [];
        $this->line('');
        $this->info('📈 Resultados:');
        $this->line("   • Total procesadas: {$summary['total_campaigns_processed']}");
        $this->line("   • Total conciliadas: {$summary['total_campaigns_reconciled']}");
        $this->line("   • Total errores: {$summary['total_errors']}");
        $this->line("   • Tasa de éxito: " . number_format($summary['success_rate'], 2) . "%");
        
        $this->line('');
        $this->info('📋 Por estado:');
        foreach ($summary['status_breakdown'] as $status => $count) {
            $emoji = match($status) {
                'active' => '🟢',
                'paused' => '🔴',
                'scheduled' => '🔵',
                'completed' => '✅',
                default => '❓'
            };
            $this->line("   {$emoji} " . strtoupper($status) . ": {$count} campañas");
        }
        
        // Mostrar detalles de transacciones creadas
        $this->line('');
        $this->info('💰 Transacciones contables creadas:');
        $transactions = AccountingTransaction::all();
        foreach ($transactions as $transaction) {
            $statusEmoji = match($transaction->status) {
                'completed' => '✅',
                'paused' => '🔴',
                'pending' => '⏳',
                default => '❓'
            };
            $this->line("   {$statusEmoji} {$transaction->client_name} - Ingreso: $" . number_format($transaction->income, 2) . " - Gasto: $" . number_format($transaction->expense, 2) . " - Ganancia: $" . number_format($transaction->profit, 2));
        }
    }
}
