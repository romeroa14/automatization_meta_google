<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MetaCampaignVariablesService;

class TestMetaVariables extends Command
{
    protected $signature = 'meta:test-variables';
    protected $description = 'Prueba y documenta todas las variables de Meta Campaigns';

    public function handle()
    {
        $this->info("🎯 **ANÁLISIS COMPLETO DE VARIABLES DE META CAMPAIGNS**");
        $this->newLine();

        $metaService = new MetaCampaignVariablesService();
        $allVariables = $metaService->getAllVariables();

        // Mostrar variables de CAMPAÑA
        $this->showSection('CAMPAÑA', $allVariables['campaign']);

        // Mostrar variables de CONJUNTO DE ANUNCIOS
        $this->showSection('CONJUNTO DE ANUNCIOS (AdSet)', $allVariables['adset']);

        // Mostrar variables de ANUNCIO
        $this->showSection('ANUNCIO (Ad)', $allVariables['ad']);

        // Probar validaciones
        $this->testValidations($metaService);

        return Command::SUCCESS;
    }

    private function showSection(string $title, array $variables): void
    {
        $this->info("📋 **{$title}**");
        $this->line("=" . str_repeat("=", strlen($title) + 6));

        foreach ($variables as $field => $config) {
            $required = $config['required'] ? '✅ REQUERIDO' : '⚪ OPCIONAL';
            $type = $config['type'];
            
            $this->line("🔹 **{$field}** ({$type}) - {$required}");
            $this->line("   📝 {$config['description']}");
            
            if (isset($config['options'])) {
                $this->line("   🎯 Opciones disponibles:");
                foreach ($config['options'] as $key => $value) {
                    $this->line("      • {$key}: {$value}");
                }
            }
            
            if (isset($config['example'])) {
                $example = is_array($config['example']) ? json_encode($config['example']) : $config['example'];
                $this->line("   💡 Ejemplo: {$example}");
            }
            
            if (isset($config['min'])) {
                $this->line("   📊 Mínimo: {$config['min']}");
            }
            
            if (isset($config['max_length'])) {
                $this->line("   📏 Longitud máxima: {$config['max_length']}");
            }
            
            $this->newLine();
        }
    }

    private function testValidations(MetaCampaignVariablesService $metaService): void
    {
        $this->info("🧪 **PRUEBAS DE VALIDACIÓN**");
        $this->line("=" . str_repeat("=", 25));

        // Probar validación de campaña
        $this->info("📋 Probando validación de CAMPAÑA:");
        $campaignData = [
            'name' => 'Campaña Test',
            'objective' => 'CONVERSIONS',
            'status' => 'PAUSED'
        ];
        
        $errors = $metaService->validateCampaignData($campaignData);
        if (empty($errors)) {
            $this->info("   ✅ Datos de campaña válidos");
        } else {
            $this->error("   ❌ Errores encontrados:");
            foreach ($errors as $error) {
                $this->error("      • {$error}");
            }
        }

        // Probar validación de conjunto de anuncios
        $this->info("📋 Probando validación de CONJUNTO DE ANUNCIOS:");
        $adSetData = [
            'name' => 'Conjunto Test',
            'campaign_id' => '123456789',
            'optimization_goal' => 'CONVERSIONS',
            'billing_event' => 'CONVERSIONS',
            'daily_budget' => 1000,
            'targeting' => [
                'geo_locations' => ['countries' => ['US']],
                'age_min' => 18,
                'age_max' => 65
            ]
        ];
        
        $errors = $metaService->validateAdSetData($adSetData);
        if (empty($errors)) {
            $this->info("   ✅ Datos de conjunto de anuncios válidos");
        } else {
            $this->error("   ❌ Errores encontrados:");
            foreach ($errors as $error) {
                $this->error("      • {$error}");
            }
        }

        // Probar validación de anuncio
        $this->info("📋 Probando validación de ANUNCIO:");
        $adData = [
            'name' => 'Anuncio Test',
            'adset_id' => '123456789',
            'status' => 'PAUSED',
            'creative' => [
                'object_story_spec' => [
                    'page_id' => '123456789',
                    'link_data' => [
                        'message' => 'Mensaje del anuncio',
                        'link' => 'https://example.com'
                    ]
                ]
            ]
        ];
        
        $errors = $metaService->validateAdData($adData);
        if (empty($errors)) {
            $this->info("   ✅ Datos de anuncio válidos");
        } else {
            $this->error("   ❌ Errores encontrados:");
            foreach ($errors as $error) {
                $this->error("      • {$error}");
            }
        }

        $this->newLine();
        $this->info("🎉 **ANÁLISIS COMPLETADO**");
        
        // Contar variables
        $allVariables = $metaService->getAllVariables();
        $this->info("📊 Total de variables documentadas: " . $this->countTotalVariables($allVariables));
    }

    private function countTotalVariables(array $allVariables): int
    {
        $count = 0;
        foreach ($allVariables as $section => $variables) {
            $count += count($variables);
        }
        return $count;
    }
}
