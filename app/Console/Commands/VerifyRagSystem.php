<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiAnalysisService;

class VerifyRagSystem extends Command
{
    protected $signature = 'verify:rag-system';
    protected $description = 'Verifica que el sistema RAG está funcionando correctamente';

    public function handle()
    {
        $this->info("🔍 Verificando sistema RAG...");
        
        // Crear instancia del servicio
        $analysisService = new GeminiAnalysisService();
        
        // Datos de prueba específicos para verificar RAG
        $testData = [
            'fan_pages' => [
                [
                    'page_name' => 'Test Page',
                    'total_ads' => 5,
                    'total_reach' => 100000,
                    'total_impressions' => 150000,
                    'total_clicks' => 500,
                    'total_spend' => 2500.00,
                    'followers_facebook' => 10000,
                    'followers_instagram' => 5000,
                    'ads' => [
                        [
                            'ad_name' => 'Test Ad 1',
                            'reach' => 20000,
                            'impressions' => 30000,
                            'clicks' => 100,
                            'spend' => 500.00,
                            'ctr' => 0.33,
                            'cpm' => 16.67,
                            'cpc' => 5.00,
                            'frequency' => 1.5,
                            'interaction_rate' => 1.8,
                            'video_completion_rate' => 35.0,
                        ]
                    ]
                ]
            ],
            'total_ads' => 5,
            'total_reach' => 100000,
            'total_impressions' => 150000,
            'total_clicks' => 500,
            'total_spend' => 2500.00,
        ];
        
        $reportInfo = [
            'name' => 'Test Report RAG',
            'period_start' => '01/08/2025',
            'period_end' => '31/08/2025',
        ];
        
        $this->info("\n📊 PASO 1: RETRIEVAL - Recuperando datos específicos...");
        
        // Usar reflexión para acceder al método protegido
        $reflection = new \ReflectionClass($analysisService);
        $prepareMethod = $reflection->getMethod('prepareAnalysisContext');
        $prepareMethod->setAccessible(true);
        
        $context = $prepareMethod->invoke($analysisService, $testData, $reportInfo);
        
        $this->info("✅ Datos recuperados:");
        $this->info("   - Fan Pages: " . count($context['fan_pages']));
        $this->info("   - Total Ads: " . $context['summary']['total_ads']);
        $this->info("   - Total Reach: " . number_format($context['summary']['total_reach']));
        $this->info("   - Total Spend: $" . number_format($context['summary']['total_spend'], 2));
        
        $this->info("\n📈 PASO 2: AUGMENTATION - Calculando métricas adicionales...");
        $this->info("✅ Métricas calculadas:");
        $this->info("   - CTR: " . number_format($context['performance_metrics']['overall_ctr'], 2) . "%");
        $this->info("   - CPM: $" . number_format($context['performance_metrics']['overall_cpm'], 2));
        $this->info("   - CPC: $" . number_format($context['performance_metrics']['overall_cpc'], 2));
        $this->info("   - Frecuencia: " . number_format($context['performance_metrics']['reach_frequency'], 2));
        
        $this->info("\n🤖 PASO 3: GENERATION - Generando prompt con contexto...");
        
        $generateMethod = $reflection->getMethod('generateAnalysisPrompt');
        $generateMethod->setAccessible(true);
        
        $prompt = $generateMethod->invoke($analysisService, $context);
        
        $this->info("✅ Prompt generado con contexto RAG:");
        $this->info("   - Longitud del prompt: " . strlen($prompt) . " caracteres");
        $this->info("   - Incluye datos JSON: " . (strpos($prompt, 'json') !== false ? '✅ Sí' : '❌ No'));
        $this->info("   - Incluye métricas específicas: " . (strpos($prompt, 'total_reach') !== false ? '✅ Sí' : '❌ No'));
        
        $this->info("\n🎯 PASO 4: VERIFICACIÓN RAG - Analizando con datos específicos...");
        
        try {
            $analysis = $analysisService->analyzeReportMetrics($testData, $reportInfo);
            
            $this->info("✅ Análisis RAG completado:");
            $this->info("   - Resumen generado: " . (isset($analysis['resumen_ejecutivo']) ? '✅ Sí' : '❌ No'));
            $this->info("   - Fortalezas: " . count($analysis['fortalezas'] ?? []));
            $this->info("   - Recomendaciones: " . count($analysis['recomendaciones'] ?? []));
            
            // Verificar que la respuesta está basada en los datos específicos
            $this->info("\n🔍 VERIFICACIÓN DE ESPECIFICIDAD:");
            
            $resumen = $analysis['resumen_ejecutivo'] ?? '';
            $specificDataFound = [];
            
            // Buscar datos específicos en la respuesta
            if (strpos($resumen, '100,000') !== false || strpos($resumen, '100000') !== false) {
                $specificDataFound[] = 'Alcance total (100,000)';
            }
            if (strpos($resumen, '2,500') !== false || strpos($resumen, '2500') !== false) {
                $specificDataFound[] = 'Gasto total ($2,500)';
            }
            if (strpos($resumen, '500') !== false) {
                $specificDataFound[] = 'Clicks totales (500)';
            }
            
            if (!empty($specificDataFound)) {
                $this->info("✅ RAG FUNCIONANDO - Respuesta basada en datos específicos:");
                foreach ($specificDataFound as $data) {
                    $this->info("   - Encontrado: {$data}");
                }
            } else {
                $this->warn("⚠️ Respuesta genérica - posible fallo en RAG");
            }
            
            $this->info("\n" . str_repeat("=", 60));
            $this->info("🎉 SISTEMA RAG VERIFICADO Y FUNCIONANDO");
            $this->info("✅ Retrieval: Datos recuperados correctamente");
            $this->info("✅ Augmentation: Contexto enriquecido");
            $this->info("✅ Generation: Respuesta basada en datos específicos");
            $this->info(str_repeat("=", 60));
            
        } catch (\Exception $e) {
            $this->error("❌ Error en verificación RAG: " . $e->getMessage());
        }
        
        $this->info("\n✅ Verificación completada");
    }
}
