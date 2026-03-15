<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MetaApiService;
use App\Models\FacebookAccount;

class TestMetaApi extends Command
{
    protected $signature = 'meta:test-api {facebook_account_id}';
    protected $description = 'Prueba la API de Meta con una cuenta de Facebook';

    public function handle()
    {
        $facebookAccountId = $this->argument('facebook_account_id');
        
        $this->info("🎯 **PRUEBA DE API DE META**");
        $this->info("Facebook Account ID: {$facebookAccountId}");
        $this->newLine();

        // Obtener cuenta de Facebook
        $facebookAccount = FacebookAccount::find($facebookAccountId);
        
        if (!$facebookAccount) {
            $this->error("❌ Cuenta de Facebook no encontrada con ID: {$facebookAccountId}");
            return Command::FAILURE;
        }

        $this->info("📱 **Cuenta de Facebook:**");
        $this->line("• Nombre: {$facebookAccount->account_name}");
        $this->line("• App ID: {$facebookAccount->app_id}");
        $this->line("• Token: {$facebookAccount->masked_access_token}");
        $this->line("• Estado: " . ($facebookAccount->is_active ? 'Activa' : 'Inactiva'));
        $this->newLine();

        $metaService = new MetaApiService();

        // Paso 1: Validar token
        $this->info("🔐 **Paso 1: Validar Token de Acceso**");
        $this->line("=" . str_repeat("=", 35));
        
        if ($metaService->validateAccessToken($facebookAccount)) {
            $this->info("✅ Token de acceso válido");
        } else {
            $this->error("❌ Token de acceso inválido");
            return Command::FAILURE;
        }
        $this->newLine();

        // Paso 2: Obtener cuentas publicitarias
        $this->info("💰 **Paso 2: Obtener Cuentas Publicitarias**");
        $this->line("=" . str_repeat("=", 40));
        
        $adAccounts = $metaService->getAdAccounts($facebookAccount);
        
        if (empty($adAccounts)) {
            $this->error("❌ No se encontraron cuentas publicitarias");
            return Command::FAILURE;
        }

        $this->info("✅ Se encontraron " . count($adAccounts) . " cuentas publicitarias:");
        $this->newLine();
        
        foreach ($adAccounts as $index => $account) {
            $number = $index + 1;
            $this->line("{$number}. **{$account['name']}** - {$account['id']}");
            $this->line("   💰 Moneda: {$account['currency']}");
            $this->line("   📊 Estado: {$account['status']}");
            $this->line("   🌍 Zona horaria: {$account['timezone']}");
            $this->line("   💵 Gasto: \${$account['amount_spent']}");
            $this->line("   💳 Balance: \${$account['balance']}");
            $this->newLine();
        }

        // Paso 3: Obtener fanpages
        $this->info("📱 **Paso 3: Obtener Fanpages**");
        $this->line("=" . str_repeat("=", 25));
        
        $pages = $metaService->getPages($facebookAccount);
        
        if (empty($pages)) {
            $this->error("❌ No se encontraron fanpages");
            return Command::FAILURE;
        }

        $this->info("✅ Se encontraron " . count($pages) . " fanpages:");
        $this->newLine();
        
        foreach ($pages as $index => $page) {
            $number = $index + 1;
            $this->line("{$number}. **{$page['name']}** - {$page['id']}");
            $this->line("   📂 Categoría: {$page['category']}");
            $this->line("   🔑 Token: " . ($page['access_token'] ? 'Disponible' : 'No disponible'));
            $this->line("   📋 Tareas: " . implode(', ', $page['tasks']));
            $this->newLine();
        }

        // Paso 4: Mostrar objetivos de campaña
        $this->info("🎯 **Paso 4: Objetivos de Campaña Disponibles**");
        $this->line("=" . str_repeat("=", 40));
        
        $objectives = $metaService->getCampaignObjectives();
        
        $this->info("✅ Objetivos disponibles:");
        $this->newLine();
        
        foreach ($objectives as $key => $description) {
            $this->line("• **{$key}**: {$description}");
        }

        $this->newLine();
        $this->info("🎉 **PRUEBA COMPLETADA EXITOSAMENTE**");
        $this->info("📊 Resumen:");
        $this->info("   • Cuentas publicitarias: " . count($adAccounts));
        $this->info("   • Fanpages: " . count($pages));
        $this->info("   • Objetivos de campaña: " . count($objectives));

        return Command::SUCCESS;
    }
}
