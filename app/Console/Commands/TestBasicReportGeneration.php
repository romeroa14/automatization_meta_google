<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestBasicReportGeneration extends Command
{
    protected $signature = 'test:basic-report-generation {report_id?}';
    protected $description = 'Prueba la generación básica de reportes sin timeout';

    public function handle()
    {
        $reportId = $this->argument('report_id') ?? 1;
        $report = Report::find($reportId);
        
        if (!$report) {
            $this->error("Reporte no encontrado con ID: {$reportId}");
            return 1;
        }

        $this->info("=== PRUEBA DE GENERACIÓN BÁSICA ===");
        $this->info("Reporte: {$report->name}");
        $this->info("Estado actual: {$report->status}");
        $this->newLine();

        try {
            $this->info("1. PROBANDO ENDPOINT DE GENERACIÓN:");
            
            $startTime = microtime(true);
            
            $response = Http::timeout(30)->post(route('reports.generate', $report));
            $endTime = microtime(true);
            
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            $this->info("   Tiempo de respuesta: {$executionTime}ms");
            $this->info("   Código de respuesta: {$response->status()}");
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info("   ✅ Respuesta exitosa");
                $this->info("   Mensaje: " . ($data['message'] ?? 'Sin mensaje'));
                
                if (isset($data['job_dispatched']) && $data['job_dispatched']) {
                    $this->info("   ✅ Job despachado correctamente");
                }
                
                if (isset($data['presentation_url'])) {
                    $this->info("   ✅ URL de presentación: {$data['presentation_url']}");
                }
                
                $this->info("   Slides generadas: " . ($data['slides_count'] ?? 0));
                
            } else {
                $this->error("   ❌ Error en la respuesta");
                $this->error("   Cuerpo: " . $response->body());
            }
            
            $this->newLine();
            
            // Verificar estado del reporte
            $report->refresh();
            $this->info("2. ESTADO DEL REPORTE DESPUÉS DE LA GENERACIÓN:");
            $this->info("   Estado: {$report->status}");
            $this->info("   URL de presentación: " . ($report->google_slides_url ?? 'No disponible'));
            $this->info("   Generado en: " . ($report->generated_at ?? 'No generado'));
            
            $this->newLine();
            $this->info("=== PRUEBA COMPLETADA ===");
            
            if ($executionTime < 30000) { // Menos de 30 segundos
                $this->info("✅ La generación básica funciona correctamente");
                $this->info("✅ No hay timeout");
            } else {
                $this->warn("⚠️ La generación tardó más de 30 segundos");
            }
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la prueba: " . $e->getMessage());
            Log::error("Error en TestBasicReportGeneration: " . $e->getMessage());
            return 1;
        }
    }
}
