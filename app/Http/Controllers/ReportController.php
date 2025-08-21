<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\GoogleSlidesReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected GoogleSlidesReportService $reportService;

    public function __construct(GoogleSlidesReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Genera un reporte manualmente
     */
    public function generateReport(Request $request, Report $report): JsonResponse
    {
        try {
            Log::info("🎯 Iniciando generación manual de reporte: {$report->name}");

            // Verificar que el reporte esté en estado válido
            if (!in_array($report->status, ['draft', 'failed'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'El reporte no puede ser generado en su estado actual',
                ], 400);
            }

            // Marcar como generando
            $report->markAsGenerating();

            // Generar el reporte
            $result = $this->reportService->generateReport($report);

            // Actualizar estado del reporte
            $this->reportService->updateReportStatus($report, $result);

            if ($result['success']) {
                Log::info("✅ Reporte generado exitosamente: {$result['presentation_url']}");
                
                return response()->json([
                    'success' => true,
                    'message' => 'Reporte generado exitosamente',
                    'presentation_url' => $result['presentation_url'],
                    'slides_count' => $result['slides_count'],
                ]);
            } else {
                Log::error("❌ Error generando reporte: {$result['error']}");
                
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("❌ Excepción generando reporte: " . $e->getMessage());
            
            // Marcar como fallido
            $report->markAsFailed();
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el estado de un reporte
     */
    public function getReportStatus(Report $report): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => $report->status,
            'status_label' => $report->status_label,
            'generated_at' => $report->generated_at,
            'presentation_url' => $report->google_slides_url,
        ]);
    }

    /**
     * Obtiene estadísticas de un reporte
     */
    public function getReportStats(Report $report): JsonResponse
    {
        return response()->json([
            'success' => true,
            'stats' => [
                'total_brands' => $report->total_brands,
                'total_campaigns' => $report->total_campaigns,
                'total_reach' => $report->total_reach,
                'total_impressions' => $report->total_impressions,
                'total_clicks' => $report->total_clicks,
                'total_spend' => $report->total_spend,
                'period_days' => $report->period_days,
            ],
        ]);
    }
}
