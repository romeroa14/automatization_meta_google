<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use FacebookAds\Api;
use FacebookAds\Object\User;
use FacebookAds\Object\AdAccount;

class TestFacebookToken extends Command
{
    protected $signature = 'facebook:test-token {account_id?}';
    protected $description = 'Prueba el access token de Facebook y verifica permisos';

    public function handle()
    {
        $accountId = $this->argument('account_id') ?? 1;
        
        $this->info('🔍 Probando access token de Facebook...');
        
        try {
            // Obtener cuenta de Facebook
            $account = \App\Models\FacebookAccount::find($accountId);
            
            if (!$account) {
                $this->error("❌ No se encontró la cuenta de Facebook con ID: {$accountId}");
                return 1;
            }
            
            $this->info("📱 Usando cuenta: {$account->account_name}");
            
            // Inicializar Facebook API sin appsecret_proof
            Api::init($account->app_id, $account->app_secret, $account->access_token);
            Api::instance()->setDefaultGraphVersion('v18.0');
            
            $this->info('✅ Facebook API inicializada correctamente');
            
            // 1. Probar obtener información del usuario
            $this->info('👤 Probando obtención de información del usuario...');
            try {
                $user = new User('10232575857351584');
                $userData = $user->getSelf(['id', 'name', 'email']);
                $this->info("✅ Usuario: {$userData->name} (ID: {$userData->id})");
            } catch (\Exception $e) {
                $this->error("❌ Error obteniendo usuario: " . $e->getMessage());
            }
            
            // 2. Probar obtener páginas
            $this->info('📄 Probando obtención de páginas...');
            try {
                $user = new User('10232575857351584');
                $pages = $user->getAccounts(['id', 'name', 'category', 'access_token'], [
                    'type' => 'page'
                ]);
                
                $pagesCount = 0;
                foreach ($pages as $page) {
                    $pagesCount++;
                    $this->info("  📄 {$page->name} (ID: {$page->id}, Categoría: {$page->category})");
                }
                
                if ($pagesCount > 0) {
                    $this->info("✅ Se encontraron {$pagesCount} páginas");
                } else {
                    $this->warn("⚠️ No se encontraron páginas");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error obteniendo páginas: " . $e->getMessage());
            }
            
            // 3. Probar obtener cuentas publicitarias
            $this->info('💰 Probando obtención de cuentas publicitarias...');
            try {
                $user = new User('10232575857351584');
                $adAccounts = $user->getAdAccounts(['id', 'name', 'account_status', 'account_type']);
                
                $accountsCount = 0;
                foreach ($adAccounts as $adAccount) {
                    $accountsCount++;
                    $this->info("  💰 {$adAccount->name} (ID: {$adAccount->id}, Tipo: {$adAccount->account_type}, Estado: {$adAccount->account_status})");
                }
                
                if ($accountsCount > 0) {
                    $this->info("✅ Se encontraron {$accountsCount} cuentas publicitarias");
                } else {
                    $this->warn("⚠️ No se encontraron cuentas publicitarias");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error obteniendo cuentas publicitarias: " . $e->getMessage());
            }
            
            // 4. Probar obtener campañas de una cuenta específica
            if ($account->selected_ad_account_id) {
                $this->info("🎯 Probando obtención de campañas de la cuenta: {$account->selected_ad_account_id}");
                try {
                    $adAccount = new AdAccount('act_' . $account->selected_ad_account_id);
                    $campaigns = $adAccount->getCampaigns(['id', 'name', 'status']);
                    
                    $campaignsCount = 0;
                    foreach ($campaigns as $campaign) {
                        $campaignsCount++;
                        $this->info("  🎯 {$campaign->name} (ID: {$campaign->id}, Estado: {$campaign->status})");
                    }
                    
                    if ($campaignsCount > 0) {
                        $this->info("✅ Se encontraron {$campaignsCount} campañas");
                    } else {
                        $this->warn("⚠️ No se encontraron campañas");
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error obteniendo campañas: " . $e->getMessage());
                }
            }
            
            $this->info('🎉 Prueba completada');
            
        } catch (\Exception $e) {
            $this->error("❌ Error general: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
