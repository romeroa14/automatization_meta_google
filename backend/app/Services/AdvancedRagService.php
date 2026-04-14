<?php

namespace App\Services;

use App\Models\AnalysisHistory;
use App\Models\Report;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AdvancedRagService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    protected $model = 'gemini-1.5-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Analiza métricas con RAG mejorado y retroalimentación
     */
    public function analyzeWithLearning(Report $report, array $facebookData): array
    {
        $startTime = microtime(true);
        
        try {
            // Paso 1: Obtener contexto histórico
            $historicalContext = $this->getHistoricalContext($facebookData);
            
            // Paso 2: Preparar datos actuales
            $currentContext = $this->prepareCurrentContext($facebookData, $report);
            
            // Paso 3: Generar prompt mejorado con contexto histórico
            $enhancedPrompt = $this->generateEnhancedPrompt($currentContext, $historicalContext);
            
            // Paso 4: Realizar análisis con IA
            $analysis = $this->callGeminiAPI($enhancedPrompt);
            
            // Paso 5: Procesar y estructurar respuesta
            $structuredAnalysis = $this->structureAnalysis($analysis);
            
            // Paso 6: Guardar en historial para aprendizaje futuro
            $processingTime = microtime(true) - $startTime;
            $this->saveToHistory($report, $facebookData, $structuredAnalysis, $enhancedPrompt, $processingTime);
            
            Log::info("✅ Análisis RAG mejorado completado en {$processingTime}s");
            
            return $structuredAnalysis;

        } catch (\Exception $e) {
            Log::error("❌ Error en análisis RAG mejorado: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Obtiene contexto histórico para mejorar el análisis
     */
    protected function getHistoricalContext(array $currentData): array
    {
        $historicalContext = [
            'similar_analyses' => [],
            'performance_trends' => [],
            'successful_recommendations' => [],
            'common_patterns' => [],
        ];

        try {
            // Obtener análisis similares exitosos
            $similarAnalyses = AnalysisHistory::getSimilarAnalyses($currentData, 3);
            
            foreach ($similarAnalyses as $analysis) {
                $historicalContext['similar_analyses'][] = [
                    'date' => $analysis->created_at->format('d/m/Y'),
                    'metrics' => $analysis->performance_metrics,
                    'successful_recommendations' => $this->extractSuccessfulRecommendations($analysis->analysis_result),
                ];
            }

            // Obtener tendencias de rendimiento
            $trends = AnalysisHistory::getPerformanceTrends(30);
            if ($trends->count() > 0) {
                $historicalContext['performance_trends'] = $this->calculateTrends($trends);
            }

            // Extraer patrones exitosos
            $historicalContext['common_patterns'] = $this->extractCommonPatterns($similarAnalyses);

        } catch (\Exception $e) {
            Log::warning("Error obteniendo contexto histórico: " . $e->getMessage());
        }

        return $historicalContext;
    }

    /**
     * Prepara el contexto actual
     */
    protected function prepareCurrentContext(array $facebookData, Report $report): array
    {
        $context = [
            'report_info' => [
                'name' => $report->name,
                'period_start' => $report->period_start->format('d/m/Y'),
                'period_end' => $report->period_end->format('d/m/Y'),
            ],
            'summary' => [
                'total_fan_pages' => count($facebookData['fan_pages']),
                'total_ads' => $facebookData['total_ads'],
                'total_reach' => $facebookData['total_reach'],
                'total_impressions' => $facebookData['total_impressions'],
                'total_clicks' => $facebookData['total_clicks'],
                'total_spend' => $facebookData['total_spend'],
            ],
            'fan_pages' => [],
            'performance_metrics' => $this->calculatePerformanceMetrics($facebookData),
        ];

        // Procesar fan pages
        foreach ($facebookData['fan_pages'] as $fanPage) {
            $context['fan_pages'][] = [
                'name' => $fanPage['page_name'],
                'ads_count' => $fanPage['total_ads'],
                'reach' => $fanPage['total_reach'],
                'impressions' => $fanPage['total_impressions'],
                'clicks' => $fanPage['total_clicks'],
                'spend' => $fanPage['total_spend'],
                'followers_facebook' => $fanPage['followers_facebook'],
                'followers_instagram' => $fanPage['followers_instagram'],
                'ads' => $fanPage['ads'] ?? [],
            ];
        }

        return $context;
    }

    /**
     * Genera prompt mejorado con contexto histórico
     */
    protected function generateEnhancedPrompt(array $currentContext, array $historicalContext): string
    {
        $jsonCurrent = json_encode($currentContext, JSON_PRETTY_PRINT);
        $jsonHistorical = json_encode($historicalContext, JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres un experto analista de marketing digital especializado en campañas de Facebook Ads con experiencia en análisis de datos históricos y tendencias de rendimiento.

**DATOS ACTUALES DE LA CAMPAÑA:**
```json
{$jsonCurrent}
```

**CONTEXTO HISTÓRICO Y APRENDIZAJE PREVIO:**
```json
{$jsonHistorical}
```

**INSTRUCCIONES ESPECÍFICAS:**
1. Analiza el rendimiento actual comparándolo con tendencias históricas
2. Identifica patrones de mejora basados en análisis previos exitosos
3. Proporciona recomendaciones que han funcionado en casos similares
4. Considera las tendencias de rendimiento para predicciones futuras
5. Aplica aprendizajes de campañas anteriores exitosas

**FORMATO DE RESPUESTA (JSON):**
```json
{
  "resumen_ejecutivo": "Resumen ejecutivo que incluye comparación con tendencias históricas",
  "analisis_comparativo": {
    "vs_tendencias_historicas": "Comparación con datos históricos",
    "patrones_identificados": "Patrones encontrados en análisis previos",
    "prediccion_tendencia": "Predicción basada en tendencias"
  },
  "metricas_clave": {
    "alcance_total": "Análisis del alcance con contexto histórico",
    "engagement_rate": "Análisis del engagement comparado",
    "costo_por_resultado": "Análisis de costos con tendencias",
    "rendimiento_por_fan_page": "Análisis por página con patrones históricos"
  },
  "fortalezas": [
    "Fortaleza 1 con contexto histórico",
    "Fortaleza 2 con contexto histórico"
  ],
  "areas_mejora": [
    "Área de mejora 1 basada en patrones históricos",
    "Área de mejora 2 con recomendaciones probadas"
  ],
  "conclusiones": [
    "Conclusión 1 con aprendizaje histórico",
    "Conclusión 2 con tendencias"
  ],
  "recomendaciones": [
    {
      "categoria": "Categoría",
      "recomendacion": "Recomendación basada en casos exitosos previos",
      "impacto_esperado": "Alto/Medio/Bajo",
      "prioridad": "Alta/Media/Baja",
      "basado_en_historico": "Sí/No",
      "casos_exitosos_similares": "Descripción de casos similares exitosos"
    }
  ],
  "proximos_pasos": [
    "Paso 1 basado en aprendizajes históricos",
    "Paso 2 con predicciones de tendencia"
  ],
  "predicciones": {
    "tendencia_alcance": "Predicción de alcance",
    "tendencia_costo": "Predicción de costos",
    "tendencia_engagement": "Predicción de engagement"
  }
}
```

Proporciona un análisis que combine datos actuales con aprendizajes históricos para maximizar la efectividad de las recomendaciones.
PROMPT;
    }

    /**
     * Calcula métricas de rendimiento
     */
    protected function calculatePerformanceMetrics(array $facebookData): array
    {
        $totalReach = $facebookData['total_reach'];
        $totalImpressions = $facebookData['total_impressions'];
        $totalClicks = $facebookData['total_clicks'];
        $totalSpend = $facebookData['total_spend'];

        return [
            'overall_ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'overall_cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
            'overall_cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            'reach_frequency' => $totalReach > 0 ? $totalImpressions / $totalReach : 0,
            'cost_per_reach' => $totalReach > 0 ? $totalSpend / $totalReach : 0,
        ];
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
                'maxOutputTokens' => 4000,
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
            // Intentar extraer JSON de la respuesta
            $jsonMatch = [];
            if (preg_match('/```json\s*(.*?)\s*```/s', $geminiResponse, $jsonMatch)) {
                $jsonData = json_decode($jsonMatch[1], true);
                if ($jsonData) {
                    return $jsonData;
                }
            }

            // Si no hay JSON válido, intentar parsear la respuesta completa
            $jsonData = json_decode($geminiResponse, true);
            if ($jsonData) {
                return $jsonData;
            }

            // Fallback
            return [
                'resumen_ejecutivo' => $geminiResponse,
                'analisis_comparativo' => [],
                'metricas_clave' => [],
                'fortalezas' => [],
                'areas_mejora' => [],
                'conclusiones' => [],
                'recomendaciones' => [],
                'proximos_pasos' => [],
                'predicciones' => [],
            ];

        } catch (\Exception $e) {
            Log::warning("Error estructurando análisis: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Guarda el análisis en el historial
     */
    protected function saveToHistory(Report $report, array $inputData, array $analysisResult, string $prompt, float $processingTime): void
    {
        try {
            AnalysisHistory::create([
                'report_id' => $report->id,
                'input_data' => $inputData,
                'analysis_result' => $analysisResult,
                'performance_metrics' => $this->calculatePerformanceMetrics($inputData),
                'prompt_used' => $prompt,
                'model_version' => $this->model,
                'processing_time' => $processingTime,
            ]);

            Log::info("✅ Análisis guardado en historial para aprendizaje futuro");
        } catch (\Exception $e) {
            Log::warning("Error guardando en historial: " . $e->getMessage());
        }
    }

    /**
     * Extrae recomendaciones exitosas de análisis previos
     */
    protected function extractSuccessfulRecommendations(array $analysisResult): array
    {
        $successfulRecommendations = [];
        
        if (isset($analysisResult['recomendaciones'])) {
            foreach ($analysisResult['recomendaciones'] as $recomendacion) {
                if (isset($recomendacion['impacto_esperado']) && 
                    in_array(strtolower($recomendacion['impacto_esperado']), ['alto', 'high'])) {
                    $successfulRecommendations[] = $recomendacion;
                }
            }
        }
        
        return $successfulRecommendations;
    }

    /**
     * Calcula tendencias de rendimiento
     */
    protected function calculateTrends($trends): array
    {
        $trendData = [];
        
        foreach ($trends as $trend) {
            if (isset($trend->performance_metrics)) {
                $trendData[] = [
                    'date' => $trend->created_at->format('d/m/Y'),
                    'ctr' => $trend->performance_metrics['overall_ctr'] ?? 0,
                    'cpm' => $trend->performance_metrics['overall_cpm'] ?? 0,
                    'cpc' => $trend->performance_metrics['overall_cpc'] ?? 0,
                ];
            }
        }
        
        return $trendData;
    }

    /**
     * Extrae patrones comunes de análisis exitosos
     */
    protected function extractCommonPatterns($similarAnalyses): array
    {
        $patterns = [];
        
        foreach ($similarAnalyses as $analysis) {
            if (isset($analysis->analysis_result['fortalezas'])) {
                $patterns['fortalezas_comunes'][] = $analysis->analysis_result['fortalezas'];
            }
            if (isset($analysis->analysis_result['recomendaciones'])) {
                $patterns['recomendaciones_exitosas'][] = $analysis->analysis_result['recomendaciones'];
            }
        }
        
        return $patterns;
    }

    /**
     * Análisis por defecto
     */
    protected function getDefaultAnalysis(): array
    {
        return [
            'resumen_ejecutivo' => 'Análisis automático con aprendizaje histórico.',
            'analisis_comparativo' => [],
            'metricas_clave' => [],
            'fortalezas' => [],
            'areas_mejora' => [],
            'conclusiones' => [],
            'recomendaciones' => [],
            'proximos_pasos' => [],
            'predicciones' => [],
        ];
    }
}
