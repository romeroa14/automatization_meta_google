<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\GeminiAnalysisService;

class TestGeminiAnalysis extends Command
{
    protected $signature = 'test:gemini-analysis';
    protected $description = 'Prueba el análisis de métricas con Gemini';

    public function handle()
    {
        $this->info("🤖 Probando análisis de métricas con Gemini...");
        
        // Obtener el primer reporte
        $report = Report::first();
        
        if (!$report) {
            $this->error("❌ No hay reportes en la base de datos");
            return;
        }
        
        $this->info("📄 Reporte encontrado: {$report->name}");
        
        // Crear instancia del servicio
        $analysisService = new GeminiAnalysisService();
        
        // Obtener datos de Facebook (simulados para la prueba)
        $facebookData = [
            'fan_pages' => [
                [
                    'page_name' => 'Moda Brands Shop',
                    'total_ads' => 11,
                    'total_reach' => 253873,
                    'total_impressions' => 456789,
                    'total_clicks' => 1234,
                    'total_spend' => 5678.90,
                    'followers_facebook' => 15000,
                    'followers_instagram' => 8500,
                    'ads' => [
                        [
                            'ad_name' => 'Anuncio de Verano',
                            'reach' => 45000,
                            'impressions' => 89000,
                            'clicks' => 234,
                            'spend' => 1234.56,
                            'ctr' => 0.26,
                            'cpm' => 13.87,
                            'cpc' => 5.28,
                            'frequency' => 1.98,
                            'interaction_rate' => 2.1,
                            'video_completion_rate' => 45.5,
                        ]
                    ]
                ]
            ],
            'total_ads' => 11,
            'total_reach' => 253873,
            'total_impressions' => 456789,
            'total_clicks' => 1234,
            'total_spend' => 5678.90,
        ];
        
        $reportInfo = [
            'name' => $report->name,
            'period_start' => $report->period_start->format('d/m/Y'),
            'period_end' => $report->period_end->format('d/m/Y'),
        ];
        
        $this->info("\n📊 Analizando métricas con Gemini...");
        
        try {
            $analysis = $analysisService->analyzeReportMetrics($facebookData, $reportInfo);
            
            $this->info("✅ Análisis completado exitosamente!");
            
            $this->info("\n📋 Resultados del análisis:");
            $this->info("   - Resumen Ejecutivo: " . substr($analysis['resumen_ejecutivo'] ?? 'No disponible', 0, 100) . "...");
            $this->info("   - Fortalezas: " . count($analysis['fortalezas'] ?? []));
            $this->info("   - Áreas de Mejora: " . count($analysis['areas_mejora'] ?? []));
            $this->info("   - Conclusiones: " . count($analysis['conclusiones'] ?? []));
            $this->info("   - Recomendaciones: " . count($analysis['recomendaciones'] ?? []));
            $this->info("   - Próximos Pasos: " . count($analysis['proximos_pasos'] ?? []));
            
            if (isset($analysis['fortalezas']) && !empty($analysis['fortalezas'])) {
                $this->info("\n✅ Fortalezas identificadas:");
                foreach ($analysis['fortalezas'] as $index => $fortaleza) {
                    $this->info("   " . ($index + 1) . ". {$fortaleza}");
                }
            }
            
            if (isset($analysis['recomendaciones']) && !empty($analysis['recomendaciones'])) {
                $this->info("\n🚀 Recomendaciones generadas:");
                foreach ($analysis['recomendaciones'] as $index => $recomendacion) {
                    $this->info("   " . ($index + 1) . ". [{$recomendacion['categoria']}] {$recomendacion['recomendacion']}");
                    $this->info("      Impacto: {$recomendacion['impacto_esperado']} | Prioridad: {$recomendacion['prioridad']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error en el análisis: " . $e->getMessage());
            $this->warn("💡 Asegúrate de configurar la API Key de Gemini en el archivo .env");
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
