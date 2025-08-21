<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('generate')
                ->label('Generar Reporte')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->status === 'draft' || $this->record->status === 'failed')
                ->action(function () {
                    try {
                        $response = Http::timeout(120)->post(route('reports.generate', $this->record));
                        $data = $response->json();
                        
                        if ($data['success']) {
                            Notification::make()
                                ->title('Reporte Generado')
                                ->body("El reporte se ha generado exitosamente con {$data['slides_count']} diapositivas.")
                                ->success()
                                ->send();
                                
                            // Redirigir a la presentación
                            return redirect()->away($data['presentation_url']);
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body('Error generando el reporte: ' . ($data['error'] ?? 'Error desconocido'))
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('Error de conexión: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('view_slides')
                ->label('Ver Presentación')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => $this->record->google_slides_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'completed' && $this->record->google_slides_url),
        ];
    }
}
