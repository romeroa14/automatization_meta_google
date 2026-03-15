<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'input_data',
        'analysis_result',
        'performance_metrics',
        'prompt_used',
        'model_version',
        'tokens_used',
        'processing_time',
        'feedback_data',
        'was_helpful',
        'user_notes',
    ];

    protected $casts = [
        'input_data' => 'array',
        'analysis_result' => 'array',
        'performance_metrics' => 'array',
        'feedback_data' => 'array',
        'was_helpful' => 'boolean',
        'processing_time' => 'float',
    ];

    /**
     * Relación con el reporte
     */
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Obtener análisis históricos similares
     */
    public static function getSimilarAnalyses($currentMetrics, $limit = 5)
    {
        return self::where('was_helpful', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener tendencias de rendimiento
     */
    public static function getPerformanceTrends($days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('performance_metrics')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Calcular métricas de mejora
     */
    public static function calculateImprovementMetrics()
    {
        $recentAnalyses = self::where('created_at', '>=', now()->subDays(90))
            ->whereNotNull('was_helpful')
            ->get();

        $totalAnalyses = $recentAnalyses->count();
        $helpfulAnalyses = $recentAnalyses->where('was_helpful', true)->count();

        return [
            'total_analyses' => $totalAnalyses,
            'helpful_analyses' => $helpfulAnalyses,
            'helpful_rate' => $totalAnalyses > 0 ? ($helpfulAnalyses / $totalAnalyses) * 100 : 0,
            'average_processing_time' => $recentAnalyses->avg('processing_time'),
        ];
    }
}
