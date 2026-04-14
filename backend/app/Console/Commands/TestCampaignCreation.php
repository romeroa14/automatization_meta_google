<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MetaCampaignCreatorService;
use App\Models\FacebookAccount;

class TestCampaignCreation extends Command
{
    protected $signature = 'meta:test-campaign-creation {facebook_account_id} {--dry-run : Solo validar sin crear}';
    protected $description = 'Prueba la creación de campañas reales en Meta';

    public function handle()
    {
        $facebookAccountId = $this->argument('facebook_account_id');
        $dryRun = $this->option('dry-run');
        
        $this->info("🧪 **PRUEBA DE CREACIÓN DE CAMPAÑAS REALES**");
        $this->info("Facebook Account ID: {$facebookAccountId}");
        $this->info("Modo: " . ($dryRun ? "DRY RUN (solo validación)" : "CREACIÓN REAL"));
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
        $this->newLine();

        // Datos de prueba para la campaña
        $campaignData = $this->getTestCampaignData();
        
        $this->info("📋 **Datos de la Campaña de Prueba:**");
        $this->displayCampaignData($campaignData);
        $this->newLine();

        if ($dryRun) {
            $this->info("🔍 **MODO DRY RUN - Solo Validación**");
            $this->line("=" . str_repeat("=", 35));
            
            $creator = new MetaCampaignCreatorService($facebookAccount);
            
            // Simular validación (crear instancia y validar datos)
            $reflection = new \ReflectionClass($creator);
            $validateMethod = $reflection->getMethod('validateCampaignData');
            $validateMethod->setAccessible(true);
            
            // Establecer datos de campaña
            $dataProperty = $reflection->getProperty('campaignData');
            $dataProperty->setAccessible(true);
            $dataProperty->setValue($creator, $campaignData);
            
            $isValid = $validateMethod->invoke($creator);
            
            if ($isValid) {
                $this->info("✅ **Validación exitosa**");
                $this->info("📊 Todos los datos son válidos para crear la campaña");
            } else {
                $this->error("❌ **Validación fallida**");
                
                // Obtener errores
                $errorsProperty = $reflection->getProperty('errors');
                $errorsProperty->setAccessible(true);
                $errors = $errorsProperty->getValue($creator);
                
                foreach ($errors as $error) {
                    $this->error("   • {$error}");
                }
            }
            
        } else {
            $this->info("🚀 **CREACIÓN REAL DE CAMPAÑA**");
            $this->line("=" . str_repeat("=", 30));
            
            if (!$this->confirm('¿Estás seguro de que quieres crear una campaña REAL en Meta?')) {
                $this->info("❌ Creación cancelada por el usuario");
                return Command::SUCCESS;
            }
            
            $creator = new MetaCampaignCreatorService($facebookAccount);
            $result = $creator->createCampaign($campaignData);
            
            if ($result['success']) {
                $this->info("✅ **¡Campaña creada exitosamente!**");
                $this->newLine();
                
                $this->info("📊 **Resultados:**");
                $this->line("• Campaña ID: {$result['campaign']['id']}");
                $this->line("• Conjunto de Anuncios ID: {$result['adset']['id']}");
                $this->line("• Anuncio ID: {$result['ad']['id']}");
                
                if (!empty($result['warnings'])) {
                    $this->newLine();
                    $this->warn("⚠️ **Advertencias:**");
                    foreach ($result['warnings'] as $warning) {
                        $this->warn("   • {$warning}");
                    }
                }
                
            } else {
                $this->error("❌ **Error creando campaña**");
                $this->newLine();
                
                if (isset($result['error'])) {
                    $this->error("Error: {$result['error']}");
                }
                
                if (!empty($result['errors'])) {
                    $this->error("Errores de validación:");
                    foreach ($result['errors'] as $error) {
                        $this->error("   • {$error}");
                    }
                }
            }
        }

        $this->newLine();
        $this->info("🎉 **PRUEBA COMPLETADA**");
        
        return Command::SUCCESS;
    }

    private function getTestCampaignData(): array
    {
        return [
            'name' => 'Campaña Test - ' . now()->format('Y-m-d H:i:s'),
            'objective' => 'OUTCOME_TRAFFIC',
            'ad_account_id' => 'act_665539106085344', // Usar una cuenta real
            'page_id' => '817826408071015', // Mery Vinil page
            'daily_budget' => 100, // $100 USD (mínimo requerido por esta cuenta)
            'geolocation' => 'VE', // Venezuela
            'age_min' => 18,
            'age_max' => 65,
            'genders' => [1, 2], // Ambos géneros
            'ad_copy' => '¡Descubre nuestra nueva colección! Oferta especial por tiempo limitado.',
            'ad_name' => 'Anuncio Test - Colección Nueva',
            'link' => 'https://example.com',
            'description' => 'Descubre nuestra increíble colección con descuentos especiales',
            'special_ad_categories' => []
        ];
    }

    private function displayCampaignData(array $data): void
    {
        $this->line("• Nombre: {$data['name']}");
        $this->line("• Objetivo: {$data['objective']}");
        $this->line("• Cuenta Publicitaria: {$data['ad_account_id']}");
        $this->line("• Fanpage: {$data['page_id']}");
        $this->line("• Presupuesto Diario: \${$data['daily_budget']}");
        $this->line("• Geolocalización: {$data['geolocation']}");
        $this->line("• Edad: {$data['age_min']}-{$data['age_max']}");
        $this->line("• Géneros: " . implode(', ', $data['genders']));
        $this->line("• Copy: {$data['ad_copy']}");
        $this->line("• Enlace: {$data['link']}");
    }
}
