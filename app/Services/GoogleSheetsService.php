<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GoogleSheetsService
{
    /**
     * Actualiza un Google Sheet usando el web app
     */
    public function updateSheet($spreadsheetId, $worksheetName, $data, $cellMapping)
    {
        try {
            Log::info("🔄 Actualizando Google Sheet: {$spreadsheetId}");
            Log::info("📊 Hoja: {$worksheetName}");
            
            // Preparar datos para actualización
            $updates = $this->prepareUpdates($data, $cellMapping);
            
            // Usar el web app para actualizar
            return $this->executeScriptAutomatically($spreadsheetId, $worksheetName, $updates);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando Google Sheet: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'updated_cells' => 0,
                'total_cells' => count($this->prepareUpdates($data, $cellMapping)),
                'data_synced' => $data
            ];
        }
    }

    /**
     * Prepara las actualizaciones de celdas
     */
    private function prepareUpdates($data, $cellMapping)
    {
        $updates = [];
        
        foreach ($cellMapping as $metric => $cell) {
            if (isset($data[$metric])) {
                $value = $data[$metric];
                
                // Formatear valores según el tipo
                if (in_array($metric, ['ctr', 'cpm', 'cpc'])) {
                    $updates[$cell] = number_format($value, 2);
                } elseif ($metric === 'spend') {
                    $updates[$cell] = number_format($value, 2);
                } else {
                    $updates[$cell] = number_format($value, 0);
                }
            }
        }
        
        return $updates;
    }

    /**
     * Ejecuta el web app automáticamente
     */
    private function executeScriptAutomatically($spreadsheetId, $worksheetName, $updates)
    {
        try {
            // Usar la URL universal desde las variables de entorno
            $webappUrl = config('services.google.webapp_url') ?? env('GOOGLE_WEBAPP_URL');
            
            if (empty($webappUrl)) {
                Log::error("URL del Web App Universal no configurada. Verifica GOOGLE_WEBAPP_URL en tu .env");
                return [
                    'success' => false, 
                    'message' => 'URL del Web App Universal no configurada',
                    'updated_cells' => 0,
                    'total_cells' => count($updates)
                ];
            }

            Log::info("🌐 Ejecutando web app universal: {$webappUrl}");

            $params = [
                'action' => 'update',
                'spreadsheet_id' => $spreadsheetId,
                'worksheet' => $worksheetName,
                'updates_data' => json_encode($updates),
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withOptions(['allow_redirects' => true])->get($webappUrl, $params);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['success']) && $result['success']) {
                    Log::info("✅ Web app ejecutado exitosamente: " . $result['message']);
                    
                    // Log de celdas actualizadas
                    foreach ($updates as $cell => $value) {
                        Log::info("📝 Celda {$cell} actualizada: {$value}");
                    }
                    
                    return [
                        'success' => true,
                        'message' => $result['message'],
                        'updated_cells' => count($updates),
                        'total_cells' => count($updates),
                        'data_synced' => $this->reversePrepareUpdates($updates)
                    ];
                } else {
                    Log::error("❌ Error en web app: " . ($result['message'] ?? 'Error desconocido'));
                    return [
                        'success' => false,
                        'message' => $result['message'] ?? 'Error en web app',
                        'updated_cells' => 0,
                        'total_cells' => count($updates)
                    ];
                }
            } else {
                Log::error("❌ Error HTTP: " . $response->status() . " - " . $response->body());
                return [
                    'success' => false,
                    'message' => 'Error HTTP: ' . $response->status(),
                    'updated_cells' => 0,
                    'total_cells' => count($updates)
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error ejecutando script automáticamente: ' . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage(),
                'updated_cells' => 0,
                'total_cells' => count($updates)
            ];
        }
    }

    /**
     * Convierte las actualizaciones de vuelta a datos originales
     */
    private function reversePrepareUpdates($updates)
    {
        $data = [];
        foreach ($updates as $cell => $value) {
            // Convertir de vuelta a número
            $data[$cell] = (float) str_replace(',', '', $value);
        }
        return $data;
    }

    /**
     * Genera script para configuración inicial (solo cuando se necesita)
     */
    public function generateWebAppScript($spreadsheetId, $worksheetName)
    {
        $scriptCode = $this->generateExecutableScript($spreadsheetId, $worksheetName);
        
        $scriptFile = storage_path('app/temp/google_sheets_webapp_' . date('Y-m-d_H-i-s') . '.js');
        
        if (!file_exists(dirname($scriptFile))) {
            mkdir(dirname($scriptFile), 0755, true);
        }
        
        file_put_contents($scriptFile, $scriptCode);
        
        Log::info("📝 Script de web app generado: {$scriptFile}");
        
        return [
            'success' => true,
            'message' => 'Script de web app generado para configuración inicial.',
            'script_file' => $scriptFile
        ];
    }

    /**
     * Genera un script ejecutable de Google Apps Script
     */
    private function generateExecutableScript($spreadsheetId, $worksheetName)
    {
        return "
// Google Apps Script Web App para actualizar Google Sheets
// Copia este código en https://script.google.com/

function updateMetrics(worksheetName, updatesData) {
  try {
    var spreadsheetId = '{$spreadsheetId}';
    var worksheetName = worksheetName || '{$worksheetName}';
    
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    var sheet = spreadsheet.getSheetByName(worksheetName);
    
    if (!sheet) {
      Logger.log('Error: Hoja no encontrada: ' + worksheetName);
      return false;
    }
    
    // Datos a actualizar (se pueden pasar como parámetros)
    var updates = updatesData || {};
    
    // If updates is a JSON string, parse it
    if (typeof updates === 'string') {
      try {
        updates = JSON.parse(updates);
      } catch (e) {
        Logger.log('Error parseando JSON: ' + e);
        return false;
      }
    }
    
    // Actualizar celdas
    var updatedCount = 0;
    for (var cell in updates) {
      try {
        sheet.getRange(cell).setValue(updates[cell]);
        updatedCount++;
        Logger.log('Celda ' + cell + ' actualizada: ' + updates[cell]);
      } catch (error) {
        Logger.log('Error actualizando celda ' + cell + ': ' + error);
      }
    }
    
    Logger.log('Se actualizaron ' + updatedCount + ' celdas exitosamente en la hoja: ' + worksheetName);
    return true;
    
  } catch (error) {
    Logger.log('Error: ' + error.toString());
    return false;
  }
}

// Función para web app (se puede llamar via HTTP)
function doGet(e) {
  try {
    var params = e.parameter;
    var worksheetName = params.worksheet || '{$worksheetName}';
    var updatesData = params.updates || null;
    
    Logger.log('Actualizando hoja: ' + worksheetName);
    
    var result = updateMetrics(worksheetName, updatesData);
    
    if (result) {
      return ContentService.createTextOutput(
        JSON.stringify({
          success: true,
          message: 'Google Sheet actualizado exitosamente en la hoja: ' + worksheetName,
          worksheet: worksheetName,
          timestamp: new Date().toISOString()
        })
      ).setMimeType(ContentService.MimeType.JSON);
    } else {
      return ContentService.createTextOutput(
        JSON.stringify({
          success: false,
          message: 'Error actualizando Google Sheet en la hoja: ' + worksheetName,
          worksheet: worksheetName,
          timestamp: new Date().toISOString()
        })
      ).setMimeType(ContentService.MimeType.JSON);
    }
    
  } catch (error) {
    return ContentService.createTextOutput(
      JSON.stringify({
        success: false,
        message: 'Error: ' + error.toString(),
        timestamp: new Date().toISOString()
      })
    ).setMimeType(ContentService.MimeType.JSON);
  }
}

// Función para POST (más seguro)
function doPost(e) {
  return doGet(e);
}

// Función de prueba
function testUpdate() {
  var result = updateMetrics('{$worksheetName}', null);
  if (result) {
    Logger.log('✅ Actualización completada exitosamente');
  } else {
    Logger.log('❌ Error en la actualización');
  }
}
";
    }
} 