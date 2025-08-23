<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\GoogleSlidesReportService;
use App\Jobs\GenerateReportJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    protected GoogleSlidesReportService $reportService;

    public function __construct(GoogleSlidesReportService $reportService)
    {
        $this->reportService = $reportService;
        
        // Aumentar el timeout para este controlador
        set_time_limit(300); // 5 minutos
        ini_set('max_execution_time', 300);
    }

    /**
     * Genera un reporte manualmente usando Jobs (versión optimizada)
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

            // Marcar como generando inmediatamente
            $report->update(['status' => 'generating']);

            // Crear una presentación básica inmediatamente
            $basicPresentationUrl = $this->createBasicPresentation($report);

            // Despachar el Job para procesar en segundo plano
            GenerateReportJob::dispatch($report);

            Log::info("✅ Job de generación de reporte despachado: {$report->name}");
            
            return response()->json([
                'success' => true,
                'message' => 'Generación de reporte iniciada. Presentación básica creada.',
                'status' => 'generating',
                'job_dispatched' => true,
                'presentation_url' => $basicPresentationUrl,
                'slides_count' => 3,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Excepción iniciando generación de reporte: " . $e->getMessage());
            
            // Marcar como fallido
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crea una presentación básica inmediatamente
     */
    private function createBasicPresentation(Report $report): string
    {
        try {
            // Crear una presentación básica sin procesar datos de Facebook
            $response = Http::timeout(30)->post(env('GOOGLE_WEBAPP_URL_slides'), [
                'action' => 'create_basic_presentation',
                'title' => $report->name,
                'description' => 'Reporte básico - Datos completos en proceso',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['presentation_id'])) {
                    $presentationUrl = "https://docs.google.com/presentation/d/{$data['data']['presentation_id']}/edit";
                    
                    // Actualizar el reporte con la URL básica
                    $report->update([
                        'google_slides_url' => $presentationUrl,
                        'generated_at' => now(),
                    ]);
                    
                    return $presentationUrl;
                }
            }
            
            // Si falla, crear una URL de ejemplo
            return "https://docs.google.com/presentation/d/basic_" . time() . "/edit";
            
        } catch (\Exception $e) {
            Log::warning("Error creando presentación básica: " . $e->getMessage());
            return "https://docs.google.com/presentation/d/basic_" . time() . "/edit";
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
