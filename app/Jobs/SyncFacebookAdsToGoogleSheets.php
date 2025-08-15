<?php

namespace App\Jobs;

use App\Models\TaskLog;
use App\Services\GoogleSheetsService;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFacebookAdsToGoogleSheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    protected $task;

    public function __construct($task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $startTime = microtime(true);
        
        try {
            // Crear log de inicio
            $log = TaskLog::create([
                'automation_task_id' => $this->task->id,
                'started_at' => now(),
                'status' => 'running',
                'message' => 'Iniciando sincronización...',
                'records_processed' => 0,
                'execution_time' => 0,
            ]);

            // 1. Configurar Facebook API
            $fbAccount = $this->task->facebookAccount;
            Api::init(
                $fbAccount->app_id,
                $fbAccount->app_secret,
                $fbAccount->access_token
            );

            $account = new AdAccount('act_' . $fbAccount->account_id);
            
            // 2. Obtener datos de Facebook Ads
            $insights = $this->getFacebookInsights($account);
            
            // 3. Configurar Google Sheets Service
            $googleSheet = $this->task->googleSheet;
            $sheetsService = new GoogleSheetsService();
            
            // 4. Actualizar Google Sheets
            $result = $sheetsService->updateSheet(
                $googleSheet->spreadsheet_id,
                $googleSheet->worksheet_name,
                $insights,
                $googleSheet->cell_mapping
            );
            
            // 5. Actualizar log con resultado
            $executionTime = microtime(true) - $startTime;
            
            if ($result['success']) {
                // Actualizar log con éxito
                $log->update([
                    'status' => 'success',
                    'message' => $result['message'],
                    'completed_at' => now(),
                    'execution_time' => $executionTime,
                    'records_processed' => $result['updated_cells'] ?? 0,
                    'data_synced' => $insights,
                ]);
                
                // Generar reporte adicional
                $report = "=== REPORTE DE MÉTRICAS ===\n";
                $report .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n\n";
                
                foreach ($insights as $metric => $value) {
                    $report .= ucfirst($metric) . ": " . number_format($value, 2) . "\n";
                }
                
                $report .= "\n=== FIN DEL REPORTE ===\n";
                Log::info("Reporte de métricas generado:\n" . $report);
                
            } else {
                $log->update([
                    'status' => 'failed',
                    'message' => $result['message'],
                    'completed_at' => now(),
                    'execution_time' => $executionTime,
                    'error_message' => $result['message'],
                ]);
            }
            
            // 6. Actualizar última ejecución de la tarea
            $this->task->update([
                'last_run' => now(),
                'next_run' => $this->task->calculateNextRun(),
            ]);
            
            Log::info("Sincronización completada para tarea: {$this->task->name}", [
                'task_id' => $this->task->id,
                'records_processed' => count($insights),
                'execution_time' => $executionTime,
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            // Actualizar log con error
            if (isset($log)) {
                $log->update([
                    'status' => 'failed',
                    'message' => 'Error durante la sincronización',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                    'execution_time' => $executionTime,
                ]);
            }
            
            Log::error("Error en sincronización para tarea: {$this->task->name}", [
                'task_id' => $this->task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    private function getFacebookInsights(AdAccount $account): array
    {
        $fields = [
            'campaign_name',
            'impressions',
            'clicks',
            'spend',
            'reach',
            'ctr',
            'cpm',
            'cpc',
        ];

        $params = [
            'level' => 'campaign',
            'time_range' => [
                'since' => date('Y-m-d', strtotime('-7 days')),
                'until' => date('Y-m-d'),
            ],
        ];

        try {
            $insights = $account->getInsights($fields, $params);
            $data = [];

            foreach ($insights as $insight) {
                $data[] = [
                    'campaign_name' => $insight->campaign_name ?? 'Sin nombre',
                    'impressions' => (int)($insight->impressions ?? 0),
                    'clicks' => (int)($insight->clicks ?? 0),
                    'spend' => (float)($insight->spend ?? 0),
                    'reach' => (int)($insight->reach ?? 0),
                    'ctr' => (float)($insight->ctr ?? 0),
                    'cpm' => (float)($insight->cpm ?? 0),
                    'cpc' => (float)($insight->cpc ?? 0),
                ];
            }

            // Calcular totales
            $totalImpressions = array_sum(array_column($data, 'impressions'));
            $totalClicks = array_sum(array_column($data, 'clicks'));
            $totalSpend = array_sum(array_column($data, 'spend'));
            $totalReach = array_sum(array_column($data, 'reach'));
            
            $totals = [
                'impressions' => $totalImpressions,
                'clicks' => $totalClicks,
                'spend' => $totalSpend,
                'reach' => $totalReach,
                'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
                'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
                'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            ];

            return $totals;

        } catch (\Exception $e) {
            Log::error('Error obteniendo insights de Facebook: ' . $e->getMessage());
            
            // Retornar datos de ejemplo en caso de error
            return [
                'impressions' => 0,
                'clicks' => 0,
                'spend' => 0,
                'reach' => 0,
                'ctr' => 0,
                'cpm' => 0,
                'cpc' => 0,
            ];
        }
    }
}
