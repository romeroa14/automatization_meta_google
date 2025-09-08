<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignReconciliationService;

class TestReconciliation extends Command
{
    protected $signature = 'test:reconciliation';
    protected $description = 'Probar la conciliación automática';

    public function handle()
    {
        $this->info('🔄 Probando conciliación automática...');
        
        $service = new CampaignReconciliationService();
        $results = $service->processActiveCampaigns();
        
        $this->info("Procesadas: {$results['processed']} campañas");
        $this->info("Conciliadas: {$results['reconciled']} campañas");
        $this->info("Errores: " . count($results['errors']));
        
        if (!empty($results['errors'])) {
            $this->error('Errores encontrados:');
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }
        
        // Verificar transacciones creadas
        $transactions = \App\Models\AccountingTransaction::all();
        $this->info("Transacciones creadas: {$transactions->count()}");
        
        foreach ($transactions as $transaction) {
            $this->line("📋 {$transaction->client_name}");
            $this->line("   💰 Ingreso: $" . number_format($transaction->income, 2));
            $this->line("   💸 Gasto: $" . number_format($transaction->expense, 2));
            $this->line("   💵 Ganancia: $" . number_format($transaction->profit, 2));
            $this->line("   📅 Inicio: " . ($transaction->campaign_start_date ?? 'N/A'));
            $this->line("   📅 Final: " . ($transaction->campaign_end_date ?? 'N/A'));
        }
    }
}
