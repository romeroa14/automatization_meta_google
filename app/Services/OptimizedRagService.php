<?php

namespace App\Services;

use App\Models\AnalysisHistory;
use App\Models\Report;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OptimizedRagService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    protected $model = 'gemini-1.5-flash';
    protected $maxHistoryDays = 90; // Solo mantener 90 días de historial
    protected $maxSimilarAnalyses = 5; // Limitar análisis similares

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Analiza con optimizaciones de almacenamiento
     */
    public function analyzeOptimized(Report $report, array $facebookData): array
    {
        $startTime = microtime(true);
        
        try {
            // Paso 1: Limpiar historial antiguo automáticamente
            $this->cleanOldHistory();
            
            // Paso 2: Obtener contexto histórico optimizado
            $historicalContext = $this->getOptimizedHistoricalContext($facebookData);
            
            // Paso 3: Preparar datos actuales comprimidos
            $currentContext = $this->prepareCompressedContext($facebookData, $report);
            
            // Paso 4: Generar prompt optimizado
            $enhancedPrompt = $this->generateOptimizedPrompt($currentContext, $historicalContext);
            
            // Paso 5: Realizar análisis
            $analysis = $this->callGeminiAPI($enhancedPrompt);
            $structuredAnalysis = $this->structureAnalysis($analysis);
            
            // Paso 6: Guardar optimizado (solo datos esenciales)
            $processingTime = microtime(true) - $startTime;
            $this->saveOptimizedHistory($report, $facebookData, $structuredAnalysis, $enhancedPrompt, $processingTime);
            
            Log::info("✅ Análisis RAG optimizado completado en {$processingTime}s");
            
            return $structuredAnalysis;

        } catch (\Exception $e) {
            Log::error("❌ Error en análisis RAG optimizado: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Limpia historial antiguo automáticamente
     */
    protected function cleanOldHistory(): void
    {
        try {
            $deletedCount = AnalysisHistory::where('created_at', '<', now()->subDays($this->maxHistoryDays))->delete();
            if ($deletedCount > 0) {
                Log::info("🧹 Limpiados {$deletedCount} registros antiguos del historial");
            }
        } catch (\Exception $e) {
            Log::warning("Error limpiando historial: " . $e->getMessage());
        }
    }

    /**
     * Obtiene contexto histórico optimizado
     */
    protected function getOptimizedHistoricalContext(array $currentData): array
    {
        $historicalContext = [
            'similar_analyses' => [],
            'performance_trends' => [],
            'successful_patterns' => [],
        ];

        try {
            // Usar cache para análisis similares
            $cacheKey = 'similar_analyses_' . md5(json_encode($currentData));
            $similarAnalyses = Cache::remember($cacheKey, 3600, function () {
                return AnalysisHistory::where('was_helpful', true)
                    ->orderBy('created_at', 'desc')
                    ->limit($this->maxSimilarAnalyses)
                    ->get(['analysis_result', 'performance_metrics', 'created_at']);
            });
            
            foreach ($similarAnalyses as $analysis) {
                $historicalContext['similar_analyses'][] = [
                    'date' => $analysis->created_at->format('d/m/Y'),
                    'key_metrics' => $this->extractKeyMetrics($analysis->performance_metrics),
                    'top_recommendations' => $this->extractTopRecommendations($analysis->analysis_result),
                ];
            }

            // Obtener tendencias comprimidas
            $trends = Cache::remember('performance_trends', 1800, function () {
                return AnalysisHistory::where('created_at', '>=', now()->subDays(30))
                    ->get(['performance_metrics', 'created_at'])
                    ->groupBy(function ($item) {
                        return $item->created_at->format('Y-m-d');
                    })
                    ->map(function ($group) {
                        return $group->avg('performance_metrics.overall_ctr');
                    });
            });
            
            $historicalContext['performance_trends'] = $trends->toArray();

        } catch (\Exception $e) {
            Log::warning("Error obteniendo contexto histórico optimizado: " . $e->getMessage());
        }

        return $historicalContext;
    }

    /**
     * Prepara contexto comprimido
     */
    protected function prepareCompressedContext(array $facebookData, Report $report): array
    {
        return [
            'report_info' => [
                'name' => $report->name,
                'period' => $report->period_start->format('d/m/Y') . ' - ' . $report->period_end->format('d/m/Y'),
            ],
            'summary' => [
                'fan_pages' => count($facebookData['fan_pages']),
                'ads' => $facebookData['total_ads'],
                'reach' => $facebookData['total_reach'],
                'impressions' => $facebookData['total_impressions'],
                'clicks' => $facebookData['total_clicks'],
                'spend' => $facebookData['total_spend'],
            ],
            'key_metrics' => $this->calculateKeyMetrics($facebookData),
            'top_performers' => $this->extractTopPerformers($facebookData),
        ];
    }

    /**
     * Extrae métricas clave comprimidas
     */
    protected function calculateKeyMetrics(array $facebookData): array
    {
        $totalReach = $facebookData['total_reach'];
        $totalImpressions = $facebookData['total_impressions'];
        $totalClicks = $facebookData['total_clicks'];
        $totalSpend = $facebookData['total_spend'];

        return [
            'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
            'cpm' => $totalImpressions > 0 ? round(($totalSpend / $totalImpressions) * 1000, 2) : 0,
            'cpc' => $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : 0,
            'frequency' => $totalReach > 0 ? round($totalImpressions / $totalReach, 2) : 0,
        ];
    }

    /**
     * Extrae top performers comprimidos
     */
    protected function extractTopPerformers(array $facebookData): array
    {
        $topPerformers = [];
        
        foreach ($facebookData['fan_pages'] as $fanPage) {
            $topPerformers[] = [
                'name' => $fanPage['page_name'],
                'reach' => $fanPage['total_reach'],
                'ctr' => $fanPage['total_impressions'] > 0 ? 
                    round(($fanPage['total_clicks'] / $fanPage['total_impressions']) * 100, 2) : 0,
                'spend' => $fanPage['total_spend'],
            ];
        }
        
        // Ordenar por CTR descendente
        usort($topPerformers, function ($a, $b) {
            return $b['ctr'] <=> $a['ctr'];
        });
        
        return array_slice($topPerformers, 0, 3); // Solo top 3
    }

    /**
     * Genera prompt optimizado
     */
    protected function generateOptimizedPrompt(array $currentContext, array $historicalContext): string
    {
        $jsonCurrent = json_encode($currentContext, JSON_PRETTY_PRINT);
        $jsonHistorical = json_encode($historicalContext, JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres un experto analista de marketing digital. Analiza estos datos optimizados de Facebook Ads:

**DATOS ACTUALES:**
```json
{$jsonCurrent}
```

**CONTEXTO HISTÓRICO OPTIMIZADO:**
```json
{$jsonHistorical}
```

**INSTRUCCIONES:**
1. Analiza el rendimiento actual
2. Compara con patrones históricos exitosos
3. Proporciona recomendaciones accionables
4. Incluye predicciones basadas en tendencias

**FORMATO DE RESPUESTA (JSON):**
```json
{
  "resumen_ejecutivo": "Resumen conciso con comparación histórica",
  "metricas_clave": {
    "alcance": "Análisis del alcance",
    "engagement": "Análisis del engagement",
    "costo": "Análisis de costos"
  },
  "fortalezas": ["Fortaleza 1", "Fortaleza 2"],
  "areas_mejora": ["Área 1", "Área 2"],
  "recomendaciones": [
    {
      "categoria": "Categoría",
      "recomendacion": "Recomendación concisa",
      "impacto": "Alto/Medio/Bajo",
      "prioridad": "Alta/Media/Baja"
    }
  ],
  "predicciones": {
    "tendencia": "Predicción basada en patrones históricos"
  }
}
```
PROMPT;
    }

    /**
     * Guarda historial optimizado
     */
    protected function saveOptimizedHistory(Report $report, array $inputData, array $analysisResult, string $prompt, float $processingTime): void
    {
        try {
            // Comprimir datos de entrada (solo métricas clave)
            $compressedInput = [
                'summary' => [
                    'total_ads' => $inputData['total_ads'],
                    'total_reach' => $inputData['total_reach'],
                    'total_impressions' => $inputData['total_impressions'],
                    'total_clicks' => $inputData['total_clicks'],
                    'total_spend' => $inputData['total_spend'],
                ],
                'key_metrics' => $this->calculateKeyMetrics($inputData),
            ];

            // Comprimir resultado (solo elementos esenciales)
            $compressedResult = [
                'resumen_ejecutivo' => $analysisResult['resumen_ejecutivo'] ?? '',
                'fortalezas' => array_slice($analysisResult['fortalezas'] ?? [], 0, 3),
                'areas_mejora' => array_slice($analysisResult['areas_mejora'] ?? [], 0, 3),
                'recomendaciones' => array_slice($analysisResult['recomendaciones'] ?? [], 0, 5),
            ];

            AnalysisHistory::create([
                'report_id' => $report->id,
                'input_data' => $compressedInput, // Comprimido
                'analysis_result' => $compressedResult, // Comprimido
                'performance_metrics' => $this->calculateKeyMetrics($inputData),
                'prompt_used' => substr($prompt, 0, 5000), // Limitar longitud
                'model_version' => $this->model,
                'processing_time' => $processingTime,
            ]);

            Log::info("✅ Análisis optimizado guardado en historial");
        } catch (\Exception $e) {
            Log::warning("Error guardando historial optimizado: " . $e->getMessage());
        }
    }

    /**
     * Extrae métricas clave
     */
    protected function extractKeyMetrics(array $metrics): array
    {
        return [
            'ctr' => $metrics['overall_ctr'] ?? 0,
            'cpm' => $metrics['overall_cpm'] ?? 0,
            'cpc' => $metrics['overall_cpc'] ?? 0,
        ];
    }

    /**
     * Extrae top recomendaciones
     */
    protected function extractTopRecommendations(array $analysisResult): array
    {
        $recommendations = $analysisResult['recomendaciones'] ?? [];
        
        // Filtrar solo recomendaciones de alto impacto
        $topRecommendations = array_filter($recommendations, function ($rec) {
            return isset($rec['impacto_esperado']) && 
                   in_array(strtolower($rec['impacto_esperado']), ['alto', 'high']);
        });
        
        return array_slice($topRecommendations, 0, 3); // Solo top 3
    }

    /**
     * Llama a la API de Gemini
     */
    protected function callGeminiAPI(string $prompt): string
    {
        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $response = Http::timeout(60)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 3000, // Reducido para optimización
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception("Error en API de Gemini: " . $response->body());
        }

        $data = $response->json();
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception("Respuesta inesperada de Gemini API");
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Estructura el análisis
     */
    protected function structureAnalysis(string $geminiResponse): array
    {
        try {
            $jsonMatch = [];
            if (preg_match('/```json\s*(.*?)\s*```/s', $geminiResponse, $jsonMatch)) {
                $jsonData = json_decode($jsonMatch[1], true);
                if ($jsonData) {
                    return $jsonData;
                }
            }

            $jsonData = json_decode($geminiResponse, true);
            if ($jsonData) {
                return $jsonData;
            }

            return $this->getDefaultAnalysis();

        } catch (\Exception $e) {
            Log::warning("Error estructurando análisis: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Análisis por defecto
     */
    protected function getDefaultAnalysis(): array
    {
        return [
            'resumen_ejecutivo' => 'Análisis automático optimizado.',
            'metricas_clave' => [],
            'fortalezas' => [],
            'areas_mejora' => [],
            'recomendaciones' => [],
            'predicciones' => [],
        ];
    }
}
