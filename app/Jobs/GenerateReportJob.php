<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\GoogleSlidesReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    protected $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function handle()
    {
        try {
            Log::info("🚀 Iniciando generación de reporte en Job: {$this->report->name}");

            // Marcar como generando
            $this->report->update(['status' => 'generating']);

            // Usar el servicio para generar el reporte
            $reportService = new GoogleSlidesReportService();
            $result = $reportService->generateReport($this->report);

            // Actualizar estado del reporte
            $reportService->updateReportStatus($this->report, $result);

            if ($result['success']) {
                Log::info("✅ Reporte generado exitosamente en Job: {$result['presentation_url']}");
            } else {
                Log::error("❌ Error generando reporte en Job: {$result['error']}");
            }

        } catch (\Exception $e) {
            Log::error("❌ Excepción en Job de generación de reporte: " . $e->getMessage());
            
            // Marcar como fallido
            $this->report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("❌ Job de generación de reporte falló: " . $exception->getMessage());
        
        $this->report->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
