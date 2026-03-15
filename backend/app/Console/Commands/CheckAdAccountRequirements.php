<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;
use App\Services\MetaApiService;
use Illuminate\Support\Facades\Http;

class CheckAdAccountRequirements extends Command
{
    protected $signature = 'meta:check-requirements {ad_account_id?}';
    protected $description = 'Verifica los requisitos mínimos de las cuentas publicitarias';

    public function handle()
    {
        $activeAccount = FacebookAccount::where('is_active', true)->first();
        
        if (!$activeAccount) {
            $this->error('❌ No se encontró cuenta activa');
            return;
        }

        $this->info("🔍 Verificando requisitos de cuentas publicitarias...");
        $this->info("📱 Cuenta activa: {$activeAccount->account_name}");
        $this->info("🔑 App ID: {$activeAccount->app_id}");
        $this->newLine();

        $metaService = new MetaApiService();
        $adAccounts = $metaService->getAdAccounts($activeAccount);
        
        $targetAccountId = $this->argument('ad_account_id');
        
        foreach ($adAccounts as $account) {
            if ($targetAccountId && $account['id'] !== $targetAccountId) {
                continue;
            }
            
            $this->checkAccountRequirements($account, $activeAccount);
            $this->newLine();
        }
    }
    
    private function checkAccountRequirements($account, $facebookAccount)
    {
        $this->info("📊 Cuenta: {$account['name']} ({$account['id']})");
        $this->info("💰 Moneda: {$account['currency']}");
        $this->info("📈 Estado: {$account['status']}");
        $this->info("💵 Gasto: \${$account['amount_spent']}");
        $this->info("💳 Balance: \${$account['balance']}");
        
        // Verificar requisitos mínimos intentando crear una campaña de prueba
        $this->checkMinimumBudget($account, $facebookAccount);
    }
    
    private function checkMinimumBudget($account, $facebookAccount)
    {
        $this->info("🔍 Verificando presupuesto mínimo...");
        
        $testBudgets = [1, 5, 10, 25, 50, 100];
        
        foreach ($testBudgets as $budget) {
            $this->info("  💰 Probando presupuesto: \${$budget}");
            
            try {
                $response = Http::post("https://graph.facebook.com/v18.0/{$account['id']}/campaigns", [
                    'access_token' => $facebookAccount->access_token,
                    'name' => "Test Campaign - {$budget}",
                    'objective' => 'OUTCOME_TRAFFIC',
                    'status' => 'PAUSED',
                    'special_ad_categories' => []
                ]);
                
                if ($response->successful()) {
                    $this->info("    ✅ Presupuesto \${$budget} aceptado");
                    // Limpiar campaña de prueba
                    $campaignId = $response->json()['id'];
                    Http::delete("https://graph.facebook.com/v18.0/{$campaignId}", [
                        'access_token' => $facebookAccount->access_token
                    ]);
                    break;
                } else {
                    $error = $response->json();
                    $this->info("    ❌ Presupuesto \${$budget} rechazado: " . ($error['error']['message'] ?? 'Error desconocido'));
                }
            } catch (\Exception $e) {
                $this->info("    ❌ Error probando presupuesto \${$budget}: " . $e->getMessage());
            }
        }
    }
}