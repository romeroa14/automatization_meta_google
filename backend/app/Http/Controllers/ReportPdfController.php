<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\PdfReportService;
use Illuminate\Http\Request;

class ReportPdfController extends Controller
{
    public function generatePdf(Report $report)
    {
        try {
            $pdfService = new PdfReportService();
            $result = $pdfService->generateReport($report);
            
            if ($result['success']) {
                // Actualizar el reporte con la información del PDF
                $report->update([
                    'pdf_generated' => true,
                    'pdf_url' => $result['file_url'],
                    'generated_at' => now(),
                ]);
                
                // Mostrar notificación de éxito
                session()->flash('success', 'PDF generado exitosamente');
                
                // Redirigir al PDF generado
                return redirect($result['file_url']);
            } else {
                // Mostrar notificación de error
                session()->flash('error', $result['error']);
                return back();
            }
        } catch (\Exception $e) {
            // Mostrar notificación de error
            session()->flash('error', 'Error: ' . $e->getMessage());
            return back();
        }
    }
}
