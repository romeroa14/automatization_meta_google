<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\PdfReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestPeriodControl extends Command
{
    protected $signature = 'test:period-control {report_id} {--start=} {--end=} {--preset=}';
    protected $description = 'Prueba el control del período de datos de Facebook';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);
        
        if (!$report) {
            $this->error("❌ Reporte con ID {$reportId} no encontrado");
            return 1;
        }

        $this->info("🔍 Probando control de período para reporte: {$report->name}");
        $this->info("📅 Período actual: {$report->period_start->format('d/m/Y')} - {$report->period_end->format('d/m/Y')}");
        
        // Mostrar información actual
        $this->displayCurrentPeriod($report);
        
        // Procesar opciones
        $startDate = $this->option('start');
        $endDate = $this->option('end');
        $preset = $this->option('preset');
        
        if ($preset) {
            $this->applyPresetPeriod($report, $preset);
        } elseif ($startDate && $endDate) {
            $this->applyCustomPeriod($report, $startDate, $endDate);
        } else {
            $this->interactiveMode($report);
        }
        
        return 0;
    }

    protected function displayCurrentPeriod(Report $report): void
    {
        $daysDiff = $report->period_start->diffInDays($report->period_end) + 1;
        
        $this->line("\n📊 INFORMACIÓN ACTUAL:");
        $this->line("   • Fecha inicio: {$report->period_start->format('d/m/Y')}");
        $this->line("   • Fecha fin: {$report->period_end->format('d/m/Y')}");
        $this->line("   • Días totales: {$daysDiff}");
        $this->line("   • Estado: {$report->status}");
        $this->line("   • Datos generados: " . ($report->generated_data ? 'Sí' : 'No'));
        $this->line("   • PDF generado: " . ($report->pdf_generated ? 'Sí' : 'No'));
        $this->line("   • Slides generados: " . ($report->google_slides_url ? 'Sí' : 'No'));
    }

    protected function applyPresetPeriod(Report $report, string $preset): void
    {
        $this->info("\n🎯 Aplicando período predefinido: {$preset}");
        
        $dates = $this->calculatePresetDates($preset);
        
        if (!$dates) {
            $this->error("❌ Período predefinido '{$preset}' no válido");
            return;
        }
        
        $this->updateReportPeriod($report, $dates['start'], $dates['end']);
    }

    protected function applyCustomPeriod(Report $report, string $startDate, string $endDate): void
    {
        $this->info("\n🎯 Aplicando período personalizado: {$startDate} - {$endDate}");
        
        // Validar fechas
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $today = Carbon::today();
            
            if ($end->isAfter($today)) {
                $this->error("❌ La fecha de fin no puede ser futura");
                return;
            }
            
            if ($start->isAfter($end)) {
                $this->error("❌ La fecha de inicio debe ser anterior a la fecha de fin");
                return;
            }
            
            $daysDiff = $start->diffInDays($end);
            if ($daysDiff > 90) {
                $this->error("❌ El período máximo es de 90 días para datos de Facebook");
                return;
            }
            
            $this->updateReportPeriod($report, $startDate, $endDate);
            
        } catch (\Exception $e) {
            $this->error("❌ Error validando fechas: " . $e->getMessage());
        }
    }

    protected function interactiveMode(Report $report): void
    {
        $this->line("\n🎮 MODO INTERACTIVO");
        $this->line("Opciones disponibles:");
        $this->line("1. Últimos 7 días");
        $this->line("2. Últimos 14 días");
        $this->line("3. Últimos 30 días");
        $this->line("4. Últimos 90 días");
        $this->line("5. Este mes");
        $this->line("6. Mes pasado");
        $this->line("7. Personalizado");
        $this->line("8. Probar datos con período actual");
        $this->line("9. Salir");
        
        $choice = $this->ask('Selecciona una opción (1-9)');
        
        switch ($choice) {
            case '1':
                $this->applyPresetPeriod($report, 'last_7d');
                break;
            case '2':
                $this->applyPresetPeriod($report, 'last_14d');
                break;
            case '3':
                $this->applyPresetPeriod($report, 'last_30d');
                break;
            case '4':
                $this->applyPresetPeriod($report, 'last_90d');
                break;
            case '5':
                $this->applyPresetPeriod($report, 'this_month');
                break;
            case '6':
                $this->applyPresetPeriod($report, 'last_month');
                break;
            case '7':
                $this->customPeriodInput($report);
                break;
            case '8':
                $this->testCurrentPeriodData($report);
                break;
            case '9':
                $this->info("👋 ¡Hasta luego!");
                break;
            default:
                $this->error("❌ Opción no válida");
                break;
        }
    }

    protected function customPeriodInput(Report $report): void
    {
        $startDate = $this->ask('Fecha de inicio (YYYY-MM-DD)');
        $endDate = $this->ask('Fecha de fin (YYYY-MM-DD)');
        
        if ($startDate && $endDate) {
            $this->applyCustomPeriod($report, $startDate, $endDate);
        } else {
            $this->error("❌ Fechas requeridas");
        }
    }

    protected function updateReportPeriod(Report $report, string $startDate, string $endDate): void
    {
        try {
            $oldStart = $report->period_start;
            $oldEnd = $report->period_end;
            
            // Actualizar fechas
            $report->update([
                'period_start' => $startDate,
                'period_end' => $endDate,
            ]);
            
            // Limpiar datos generados si existen
            if ($report->generated_data || $report->google_slides_url || $report->pdf_generated) {
                $report->update([
                    'generated_data' => null,
                    'google_slides_url' => null,
                    'pdf_generated' => false,
                    'pdf_url' => null,
                    'status' => 'draft',
                    'generated_at' => null,
                ]);
                
                $this->warn("⚠️  Datos anteriores eliminados para regeneración");
            }
            
            $this->info("✅ Período actualizado: {$oldStart} - {$oldEnd} → {$startDate} - {$endDate}");
            
            // Mostrar nueva información
            $report->refresh();
            $this->displayCurrentPeriod($report);
            
        } catch (\Exception $e) {
            $this->error("❌ Error actualizando período: " . $e->getMessage());
        }
    }

    protected function testCurrentPeriodData(Report $report): void
    {
        $this->info("\n🧪 Probando obtención de datos con período actual...");
        
        try {
            $pdfService = new PdfReportService();
            $facebookData = $pdfService->getFacebookDataByFanPages($report);
            
            $this->info("✅ Datos obtenidos exitosamente:");
            $this->line("   • Fan Pages: " . count($facebookData['fan_pages']));
            $this->line("   • Total anuncios: {$facebookData['total_ads']}");
            $this->line("   • Total alcance: " . number_format($facebookData['total_reach']));
            $this->line("   • Total impresiones: " . number_format($facebookData['total_impressions']));
            $this->line("   • Total clicks: " . number_format($facebookData['total_clicks']));
            $this->line("   • Total gasto: $" . number_format($facebookData['total_spend'], 2));
            
            if (!empty($facebookData['fan_pages'])) {
                $this->line("\n📊 Detalle por Fan Page:");
                foreach ($facebookData['fan_pages'] as $fanPage) {
                    $this->line("   • {$fanPage['page_name']}: {$fanPage['total_ads']} anuncios, $" . number_format($fanPage['total_spend'], 2));
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo datos: " . $e->getMessage());
            Log::error("Error en test de período: " . $e->getMessage());
        }
    }

    protected function calculatePresetDates(string $preset): ?array
    {
        $now = Carbon::now();
        
        switch ($preset) {
            case 'last_7d':
                return [
                    'start' => $now->copy()->subDays(7)->format('Y-m-d'),
                    'end' => $now->copy()->subDay()->format('Y-m-d'),
                ];
            case 'last_14d':
                return [
                    'start' => $now->copy()->subDays(14)->format('Y-m-d'),
                    'end' => $now->copy()->subDay()->format('Y-m-d'),
                ];
            case 'last_30d':
                return [
                    'start' => $now->copy()->subDays(30)->format('Y-m-d'),
                    'end' => $now->copy()->subDay()->format('Y-m-d'),
                ];
            case 'last_90d':
                return [
                    'start' => $now->copy()->subDays(90)->format('Y-m-d'),
                    'end' => $now->copy()->subDay()->format('Y-m-d'),
                ];
            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                    'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
                ];
            case 'last_month':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                    'end' => $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
                ];
            default:
                return null;
        }
    }
}
