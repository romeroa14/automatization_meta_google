<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\GoogleSlidesReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestReportDataSending extends Command
{
    protected $signature = 'reports:test-data-sending {report_id : ID del reporte}';
    protected $description = 'Prueba el envío de datos del reporte al Google Apps Script';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);

        if (!$report) {
            $this->error("❌ No se encontró el reporte con ID: {$reportId}");
            return 1;
        }

        $this->info("🧪 Probando envío de datos del reporte: {$report->name}");

        try {
            $service = new GoogleSlidesReportService();
            
            // Preparar los datos del reporte
            $reportData = $service->prepareReportData($report);
            
            $this->info("📊 Total de diapositivas a generar: " . count($reportData['slides']));
            
            // Probar la creación de presentación
            $this->info("📄 Probando creación de presentación...");
            $presentationId = $this->testCreatePresentation($report);
            
            if (!$presentationId) {
                $this->error("❌ No se pudo crear la presentación");
                return 1;
            }
            
            $this->info("✅ Presentación creada: " . $presentationId);
            
            // Probar el envío de cada diapositiva
            foreach ($reportData['slides'] as $index => $slide) {
                $this->info("📄 Probando diapositiva " . ($index + 1) . ": {$slide['type']}");
                $this->testCreateSlide($presentationId, $slide, $index);
            }
            
            $this->info("✅ Prueba completada exitosamente!");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }

    protected function testCreatePresentation(Report $report): ?string
    {
        $webAppUrl = env('GOOGLE_WEBAPP_URL_slides');
        
        $data = [
            'action' => 'create_presentation',
            'title' => $report->name,
            'description' => $report->description ?? 'Reporte estadístico generado automáticamente',
        ];

        $this->info("📤 Enviando datos de presentación: " . json_encode($data, JSON_PRETTY_PRINT));

        $response = Http::timeout(120)->post($webAppUrl, $data);
        
        $this->info("📊 Status Code: " . $response->status());
        $this->info("📄 Response: " . $response->body());

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['success']) && $responseData['success']) {
                $this->info("✅ Presentación creada exitosamente!");
                return $responseData['data']['presentation_id'] ?? null;
            } else {
                $this->error("❌ Error en la respuesta: " . ($responseData['error'] ?? 'Error desconocido'));
            }
        } else {
            $this->error("❌ Error HTTP: " . $response->status());
        }

        return null;
    }

    protected function testCreateSlide(string $presentationId, array $slideData, int $index): void
    {
        $webAppUrl = env('GOOGLE_WEBAPP_URL_slides');
        
        $data = [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $index,
            'slide_data' => $slideData,
        ];

        $this->info("📤 Enviando datos de diapositiva: " . json_encode($data, JSON_PRETTY_PRINT));

        $response = Http::timeout(120)->post($webAppUrl, $data);
        
        $this->info("📊 Status Code: " . $response->status());
        $this->info("📄 Response: " . $response->body());

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['success']) && $responseData['success']) {
                $this->info("✅ Diapositiva creada exitosamente!");
            } else {
                $this->error("❌ Error en la respuesta: " . ($responseData['error'] ?? 'Error desconocido'));
            }
        } else {
            $this->error("❌ Error HTTP: " . $response->status());
        }
    }
}
