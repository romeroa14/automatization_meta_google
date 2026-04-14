<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MetaApiService;
use App\Models\FacebookAccount;

class TestAccountBalance extends Command
{
    protected $signature = 'test:account-balance {ad_account_id?}';
    protected $description = 'Probar funcionalidad de saldo de cuenta publicitaria';

    public function handle()
    {
        $this->info('🧪 Probando funcionalidad de saldo de cuenta publicitaria...');
        
        // Obtener cuenta de Facebook activa
        $facebookAccount = FacebookAccount::where('is_active', true)->first();
        
        if (!$facebookAccount) {
            $this->error('❌ No se encontró cuenta de Facebook activa');
            return;
        }
        
        $this->info("✅ Cuenta de Facebook encontrada: {$facebookAccount->name}");
        
        // Obtener ID de cuenta publicitaria
        $adAccountId = $this->argument('ad_account_id') ?? 'act_665539106085344';
        
        $this->info("🔍 Probando con cuenta publicitaria: {$adAccountId}");
        
        // Probar servicio
        $metaApiService = new MetaApiService();
        
        // Probar getAccountBalance
        $this->info("\n📊 Probando getAccountBalance...");
        $balanceResult = $metaApiService->getAccountBalance($adAccountId, $facebookAccount->id);
        
        if ($balanceResult['success']) {
            $this->info("✅ getAccountBalance exitoso");
            $this->line("Datos: " . json_encode($balanceResult['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error("❌ Error en getAccountBalance: " . $balanceResult['error']);
        }
        
        // Probar getAccountInfo
        $this->info("\n📊 Probando getAccountInfo...");
        $infoResult = $metaApiService->getAccountInfo($adAccountId, $facebookAccount->id);
        
        if ($infoResult['success']) {
            $this->info("✅ getAccountInfo exitoso");
            $this->line("Datos: " . json_encode($infoResult['data'], JSON_PRETTY_PRINT));
        } else {
            $this->error("❌ Error en getAccountInfo: " . $infoResult['error']);
        }
        
        $this->info("\n🎉 Pruebas completadas!");
    }
}