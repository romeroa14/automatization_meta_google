<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeminiAnalysisService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    protected $model = 'gemini-1.5-flash'; // o 'gemini-1.5-pro' para más capacidad

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        
        if (!$this->apiKey) {
            Log::warning("⚠️ API Key de Gemini no configurada");
        }
    }

    /**
     * Analiza las métricas del reporte y genera conclusiones
     */
    public function analyzeReportMetrics(array $facebookData, array $reportInfo): array
    {
        try {
            if (!$this->apiKey) {
                return $this->getDefaultAnalysis();
            }

            // Preparar el contexto para el análisis
            $context = $this->prepareAnalysisContext($facebookData, $reportInfo);
            
            // Generar prompt para el análisis
            $prompt = $this->generateAnalysisPrompt($context);
            
            // Realizar análisis con Gemini
            $analysis = $this->callGeminiAPI($prompt);
            
            // Procesar y estructurar la respuesta
            $structuredAnalysis = $this->structureAnalysis($analysis);
            
            Log::info("✅ Análisis de métricas completado con Gemini");
            
            return $structuredAnalysis;

        } catch (\Exception $e) {
            Log::error("❌ Error en análisis de métricas: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Prepara el contexto para el análisis
     */
    protected function prepareAnalysisContext(array $facebookData, array $reportInfo): array
    {
        $context = [
            'report_info' => $reportInfo,
            'summary' => [
                'total_fan_pages' => count($facebookData['fan_pages']),
                'total_ads' => $facebookData['total_ads'],
                'total_reach' => $facebookData['total_reach'],
                'total_impressions' => $facebookData['total_impressions'],
                'total_clicks' => $facebookData['total_clicks'],
                'total_spend' => $facebookData['total_spend'],
            ],
            'fan_pages' => [],
            'performance_metrics' => [],
        ];

        // Procesar cada fan page
        foreach ($facebookData['fan_pages'] as $fanPage) {
            $pageData = [
                'name' => $fanPage['page_name'],
                'ads_count' => $fanPage['total_ads'],
                'reach' => $fanPage['total_reach'],
                'impressions' => $fanPage['total_impressions'],
                'clicks' => $fanPage['total_clicks'],
                'spend' => $fanPage['total_spend'],
                'followers_facebook' => $fanPage['followers_facebook'],
                'followers_instagram' => $fanPage['followers_instagram'],
                'ads' => [],
            ];

            // Procesar anuncios individuales
            foreach ($fanPage['ads'] as $ad) {
                $pageData['ads'][] = [
                    'name' => $ad['ad_name'],
                    'reach' => $ad['reach'],
                    'impressions' => $ad['impressions'],
                    'clicks' => $ad['clicks'],
                    'spend' => $ad['spend'],
                    'ctr' => $ad['ctr'],
                    'cpm' => $ad['cpm'],
                    'cpc' => $ad['cpc'],
                    'frequency' => $ad['frequency'],
                    'interaction_rate' => $ad['interaction_rate'],
                    'video_completion_rate' => $ad['video_completion_rate'],
                ];
            }

            $context['fan_pages'][] = $pageData;
        }

        // Calcular métricas de rendimiento
        $context['performance_metrics'] = $this->calculatePerformanceMetrics($facebookData);

        return $context;
    }

    /**
     * Calcula métricas de rendimiento adicionales
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
     * Genera el prompt para el análisis
     */
    protected function generateAnalysisPrompt(array $context): string
    {
        $jsonContext = json_encode($context, JSON_PRETTY_PRINT);

        return <<<PROMPT
Eres un experto analista de marketing digital especializado en campañas de Facebook Ads. Analiza los siguientes datos de campaña y proporciona un análisis detallado en español.

**DATOS DE LA CAMPAÑA:**
```json
{$jsonContext}
```

**INSTRUCCIONES:**
1. Analiza el rendimiento general de la campaña
2. Identifica fortalezas y áreas de mejora
3. Proporciona conclusiones específicas basadas en los datos
4. Genera recomendaciones accionables
5. Considera el contexto del negocio y las métricas clave

**FORMATO DE RESPUESTA (JSON):**
```json
{
  "resumen_ejecutivo": "Resumen ejecutivo de 2-3 párrafos",
  "metricas_clave": {
    "alcance_total": "Análisis del alcance",
    "engagement_rate": "Análisis del engagement",
    "costo_por_resultado": "Análisis de costos",
    "rendimiento_por_fan_page": "Análisis por página"
  },
  "fortalezas": [
    "Fortaleza 1",
    "Fortaleza 2",
    "Fortaleza 3"
  ],
  "areas_mejora": [
    "Área de mejora 1",
    "Área de mejora 2",
    "Área de mejora 3"
  ],
  "conclusiones": [
    "Conclusión 1",
    "Conclusión 2",
    "Conclusión 3"
  ],
  "recomendaciones": [
    {
      "categoria": "Optimización de Presupuesto",
      "recomendacion": "Descripción de la recomendación",
      "impacto_esperado": "Alto/Medio/Bajo",
      "prioridad": "Alta/Media/Baja"
    }
  ],
  "proximos_pasos": [
    "Paso 1",
    "Paso 2",
    "Paso 3"
  ]
}
```

Proporciona un análisis profesional, detallado y accionable basado únicamente en los datos proporcionados.
PROMPT;
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
     * Estructura el análisis de la respuesta de Gemini
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

            // Fallback: crear estructura básica
            return [
                'resumen_ejecutivo' => $geminiResponse,
                'metricas_clave' => [],
                'fortalezas' => [],
                'areas_mejora' => [],
                'conclusiones' => [],
                'recomendaciones' => [],
                'proximos_pasos' => [],
            ];

        } catch (\Exception $e) {
            Log::warning("Error estructurando análisis: " . $e->getMessage());
            return $this->getDefaultAnalysis();
        }
    }

    /**
     * Análisis por defecto cuando Gemini no está disponible
     */
    protected function getDefaultAnalysis(): array
    {
        return [
            'resumen_ejecutivo' => 'Análisis automático de métricas de campaña de Facebook Ads. Los datos han sido procesados y las métricas clave han sido calculadas para evaluar el rendimiento de la campaña.',
            'metricas_clave' => [
                'alcance_total' => 'El alcance total de la campaña muestra la cantidad de usuarios únicos alcanzados.',
                'engagement_rate' => 'La tasa de engagement indica el nivel de interacción con el contenido.',
                'costo_por_resultado' => 'El costo por resultado ayuda a evaluar la eficiencia de la inversión.',
                'rendimiento_por_fan_page' => 'El rendimiento por fan page permite identificar las páginas más efectivas.'
            ],
            'fortalezas' => [
                'Campaña activa con métricas registradas',
                'Datos detallados por anuncio disponibles',
                'Métricas de engagement calculadas'
            ],
            'areas_mejora' => [
                'Revisar optimización de presupuesto',
                'Analizar segmentación de audiencia',
                'Evaluar creativos de anuncios'
            ],
            'conclusiones' => [
                'La campaña está generando datos de rendimiento',
                'Se requieren análisis adicionales para optimización',
                'Las métricas base están siendo monitoreadas'
            ],
            'recomendaciones' => [
                [
                    'categoria' => 'Optimización General',
                    'recomendacion' => 'Revisar y ajustar el presupuesto según el rendimiento observado',
                    'impacto_esperado' => 'Medio',
                    'prioridad' => 'Alta'
                ]
            ],
            'proximos_pasos' => [
                'Configurar API Key de Gemini para análisis avanzado',
                'Revisar métricas de rendimiento semanalmente',
                'Implementar optimizaciones basadas en datos'
            ]
        ];
    }
}
