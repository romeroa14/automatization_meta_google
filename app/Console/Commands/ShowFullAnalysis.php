<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\GeminiAnalysisService;

class ShowFullAnalysis extends Command
{
    protected $signature = 'show:full-analysis';
    protected $description = 'Muestra el análisis completo de IA con todos los detalles';

    public function handle()
    {
        $this->info("🤖 Mostrando análisis completo de IA...");
        
        // Obtener el primer reporte
        $report = Report::first();
        
        if (!$report) {
            $this->error("❌ No hay reportes en la base de datos");
            return;
        }
        
        $this->info("📄 Reporte: {$report->name}");
        $this->info("📅 Período: {$report->period_start->format('d/m/Y')} - {$report->period_end->format('d/m/Y')}");
        
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
            
            $this->info("\n" . str_repeat("=", 80));
            $this->info("🤖 ANÁLISIS COMPLETO DE IA - GEMINI 2.5 FLASH");
            $this->info(str_repeat("=", 80));
            
            // Resumen Ejecutivo
            $this->info("\n📋 RESUMEN EJECUTIVO:");
            $this->info(str_repeat("-", 40));
            $this->info($analysis['resumen_ejecutivo'] ?? 'No disponible');
            
            // Métricas Clave
            if (isset($analysis['metricas_clave']) && !empty($analysis['metricas_clave'])) {
                $this->info("\n🎯 MÉTRICAS CLAVE:");
                $this->info(str_repeat("-", 40));
                foreach ($analysis['metricas_clave'] as $key => $analysis_text) {
                    $this->info("• " . strtoupper(str_replace('_', ' ', $key)) . ":");
                    $this->info("  {$analysis_text}");
                    $this->info("");
                }
            }
            
            // Fortalezas
            if (isset($analysis['fortalezas']) && !empty($analysis['fortalezas'])) {
                $this->info("\n✅ FORTALEZAS IDENTIFICADAS:");
                $this->info(str_repeat("-", 40));
                foreach ($analysis['fortalezas'] as $index => $fortaleza) {
                    $this->info("  " . ($index + 1) . ". {$fortaleza}");
                }
            }
            
            // Áreas de Mejora
            if (isset($analysis['areas_mejora']) && !empty($analysis['areas_mejora'])) {
                $this->info("\n🔧 ÁREAS DE MEJORA:");
                $this->info(str_repeat("-", 40));
                foreach ($analysis['areas_mejora'] as $index => $area) {
                    $this->info("  " . ($index + 1) . ". {$area}");
                }
            }
            
            // Conclusiones
            if (isset($analysis['conclusiones']) && !empty($analysis['conclusiones'])) {
                $this->info("\n💡 CONCLUSIONES:");
                $this->info(str_repeat("-", 40));
                foreach ($analysis['conclusiones'] as $index => $conclusion) {
                    $this->info("  " . ($index + 1) . ". {$conclusion}");
                }
            }
            
            // Recomendaciones
            if (isset($analysis['recomendaciones']) && !empty($analysis['recomendaciones'])) {
                $this->info("\n🚀 RECOMENDACIONES ACCIONABLES:");
                $this->info(str_repeat("-", 40));
                foreach ($analysis['recomendaciones'] as $index => $recomendacion) {
                    $this->info("  " . ($index + 1) . ". [{$recomendacion['categoria']}]");
                    $this->info("     {$recomendacion['recomendacion']}");
                    $this->info("     📊 Impacto: {$recomendacion['impacto_esperado']} | 🎯 Prioridad: {$recomendacion['prioridad']}");
                    $this->info("");
                }
            }
            
            // Próximos Pasos
            if (isset($analysis['proximos_pasos']) && !empty($analysis['proximos_pasos'])) {
                $this->info("\n📋 PRÓXIMOS PASOS:");
                $this->info(str_repeat("-", 40));
                foreach ($analysis['proximos_pasos'] as $index => $paso) {
                    $this->info("  " . ($index + 1) . ". {$paso}");
                }
            }
            
            $this->info("\n" . str_repeat("=", 80));
            $this->info("✅ ANÁLISIS COMPLETADO CON ÉXITO");
            $this->info("📄 Este análisis se incluye automáticamente en el PDF generado");
            $this->info(str_repeat("=", 80));
            
        } catch (\Exception $e) {
            $this->error("❌ Error en el análisis: " . $e->getMessage());
        }
        
        $this->info("\n✅ Análisis mostrado completamente");
    }
}
