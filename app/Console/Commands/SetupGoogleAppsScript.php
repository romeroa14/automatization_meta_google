<?php

namespace App\Console\Commands;

use App\Models\GoogleSheet;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;

class SetupGoogleAppsScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:setup-script {--sheet-id= : ID específico del Google Sheet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura Google Apps Script Web App para actualización automática';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configurando Google Apps Script Web App...');
        
        try {
            // Obtener Google Sheet
            $sheetId = $this->option('sheet-id');
            
            if ($sheetId) {
                $googleSheet = GoogleSheet::where('spreadsheet_id', $sheetId)->first();
            } else {
                $googleSheet = GoogleSheet::first();
            }
            
            if (!$googleSheet) {
                $this->error('❌ No se encontró ningún Google Sheet configurado.');
                return 1;
            }
            
            $this->info("📊 Configurando para: {$googleSheet->name}");
            $this->info("🆔 Spreadsheet ID: {$googleSheet->spreadsheet_id}");
            $this->info("📋 Hoja: {$googleSheet->worksheet_name}");
            
            // Generar script web app
            $scriptCode = $this->generateExecutableScript($googleSheet->spreadsheet_id, $googleSheet->worksheet_name);
            
            // Guardar script
            $scriptFile = storage_path('app/temp/google_sheets_webapp_' . date('Y-m-d_H-i-s') . '.js');
            
            if (!file_exists(dirname($scriptFile))) {
                mkdir(dirname($scriptFile), 0755, true);
            }
            
            file_put_contents($scriptFile, $scriptCode);
            
            $this->info("📝 Script Web App generado: {$scriptFile}");
            
            // Mostrar instrucciones
            $this->showInstructions($scriptFile, $googleSheet);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error configurando script: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Genera el script web app
     */
    private function generateExecutableScript($spreadsheetId, $worksheetName)
    {
        return "
// Google Apps Script Web App Universal para actualizar cualquier Google Sheet
// Este script puede manejar cualquier spreadsheet_id, cualquier hoja y cualquier mapeo de celdas
// Copia este código en https://script.google.com/

function updateMetrics(spreadsheetId, worksheetName, updatesData) {
  try {
    // Validar parámetros requeridos
    if (!spreadsheetId) {
      Logger.log('Error: spreadsheet_id es requerido');
      return false;
    }
    
    if (!worksheetName) {
      Logger.log('Error: worksheet_name es requerido');
      return false;
    }
    
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    var sheet = spreadsheet.getSheetByName(worksheetName);
    
    if (!sheet) {
      Logger.log('Error: Hoja no encontrada: ' + worksheetName + ' en spreadsheet: ' + spreadsheetId);
      return false;
    }
    
    // Datos a actualizar (se pasan como parámetros desde Laravel)
    var updates = updatesData || {};
    
    // Si updates es un string JSON, parsearlo
    if (typeof updates === 'string') {
      try {
        updates = JSON.parse(updates);
      } catch (e) {
        Logger.log('Error parseando JSON: ' + e);
        return false;
      }
    }
    
    // Validar que hay datos para actualizar
    if (Object.keys(updates).length === 0) {
      Logger.log('No hay datos para actualizar');
      return false;
    }
    
    // Actualizar celdas dinámicamente
    var updatedCount = 0;
    var errors = [];
    
    for (var cell in updates) {
      try {
        var value = updates[cell];
        sheet.getRange(cell).setValue(value);
        updatedCount++;
        Logger.log('Celda ' + cell + ' actualizada: ' + value);
      } catch (error) {
        var errorMsg = 'Error actualizando celda ' + cell + ': ' + error;
        Logger.log(errorMsg);
        errors.push(errorMsg);
      }
    }
    
    Logger.log('Se actualizaron ' + updatedCount + ' celdas exitosamente en la hoja: ' + worksheetName + ' del spreadsheet: ' + spreadsheetId);
    
    if (errors.length > 0) {
      Logger.log('Errores encontrados: ' + errors.join(', '));
    }
    
    return updatedCount > 0;
    
  } catch (error) {
    Logger.log('Error general: ' + error.toString());
    return false;
  }
}

// Función para listar hojas disponibles de cualquier spreadsheet
function listWorksheets(spreadsheetId) {
  try {
    if (!spreadsheetId) {
      Logger.log('Error: spreadsheet_id es requerido para listar hojas');
      return [];
    }
    
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    var sheets = spreadsheet.getSheets();
    
    var sheetNames = [];
    for (var i = 0; i < sheets.length; i++) {
      sheetNames.push(sheets[i].getName());
    }
    
    Logger.log('Hojas encontradas en ' + spreadsheetId + ': ' + sheetNames.join(', '));
    return sheetNames;
  } catch (error) {
    Logger.log('Error listando hojas de ' + spreadsheetId + ': ' + error);
    return [];
  }
}

// Función para verificar permisos de un spreadsheet
function checkSpreadsheetAccess(spreadsheetId) {
  try {
    if (!spreadsheetId) {
      return {
        success: false,
        error: 'spreadsheet_id es requerido'
      };
    }
    
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    
    return {
      success: true,
      spreadsheet_name: spreadsheet.getName(),
      spreadsheet_id: spreadsheetId,
      access_granted: true
    };
    
  } catch (error) {
    return {
      success: false,
      error: 'No se puede acceder al spreadsheet: ' + error.toString(),
      spreadsheet_id: spreadsheetId
    };
  }
}

// Función para web app universal (se puede llamar via HTTP)
function doGet(e) {
  try {
    var params = e.parameter;
    var action = params.action || 'update';
    
    Logger.log('=== INICIO DE SOLICITUD UNIVERSAL ===');
    Logger.log('Acción solicitada: ' + action);
    
    if (action === 'list_sheets') {
      // Listar hojas disponibles de cualquier spreadsheet
      var spreadsheetId = params.spreadsheet_id;
      
      if (!spreadsheetId) {
        return ContentService.createTextOutput(
          JSON.stringify({
            success: false,
            action: 'list_sheets',
            error: 'spreadsheet_id es requerido',
            timestamp: new Date().toISOString()
          })
        ).setMimeType(ContentService.MimeType.JSON);
      }
      
      var sheets = listWorksheets(spreadsheetId);
      
      var response = {
        success: true,
        action: 'list_sheets',
        spreadsheet_id: spreadsheetId,
        sheets: sheets,
        sheets_count: sheets.length,
        timestamp: new Date().toISOString()
      };
      
      Logger.log('✅ Lista de hojas generada para ' + spreadsheetId + ': ' + sheets.join(', '));
      return ContentService.createTextOutput(JSON.stringify(response))
        .setMimeType(ContentService.MimeType.JSON);
    }
    
    if (action === 'check_access') {
      // Verificar acceso a un spreadsheet
      var spreadsheetId = params.spreadsheet_id;
      
      if (!spreadsheetId) {
        return ContentService.createTextOutput(
          JSON.stringify({
            success: false,
            action: 'check_access',
            error: 'spreadsheet_id es requerido',
            timestamp: new Date().toISOString()
          })
        ).setMimeType(ContentService.MimeType.JSON);
      }
      
      var result = checkSpreadsheetAccess(spreadsheetId);
      result.action = 'check_access';
      result.timestamp = new Date().toISOString();
      
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }
    
    // Acción por defecto: actualizar
    var spreadsheetId = params.spreadsheet_id;
    var worksheetName = params.worksheet;
    var updatesData = params.updates || null;
    
    // Validar parámetros requeridos
    if (!spreadsheetId) {
      return ContentService.createTextOutput(
        JSON.stringify({
          success: false,
          action: 'update',
          error: 'spreadsheet_id es requerido',
          timestamp: new Date().toISOString()
        })
      ).setMimeType(ContentService.MimeType.JSON);
    }
    
    if (!worksheetName) {
      return ContentService.createTextOutput(
        JSON.stringify({
          success: false,
          action: 'update',
          error: 'worksheet es requerido',
          timestamp: new Date().toISOString()
        })
      ).setMimeType(ContentService.MimeType.JSON);
    }
    
    Logger.log('Actualizando spreadsheet: ' + spreadsheetId + ', hoja: ' + worksheetName);
    Logger.log('Datos recibidos: ' + (updatesData ? 'Sí' : 'No'));
    
    var result = updateMetrics(spreadsheetId, worksheetName, updatesData);
    
    if (result) {
      var response = {
        success: true,
        action: 'update',
        message: 'Google Sheet actualizado exitosamente',
        spreadsheet_id: spreadsheetId,
        worksheet: worksheetName,
        timestamp: new Date().toISOString(),
        updated_cells: updatesData ? Object.keys(JSON.parse(updatesData)).length : 0
      };
      
      Logger.log('✅ Actualización exitosa: ' + response.message);
      return ContentService.createTextOutput(JSON.stringify(response))
        .setMimeType(ContentService.MimeType.JSON);
    } else {
      var response = {
        success: false,
        action: 'update',
        message: 'Error actualizando Google Sheet',
        spreadsheet_id: spreadsheetId,
        worksheet: worksheetName,
        timestamp: new Date().toISOString()
      };
      
      Logger.log('❌ Error en actualización: ' + response.message);
      return ContentService.createTextOutput(JSON.stringify(response))
        .setMimeType(ContentService.MimeType.JSON);
    }
    
  } catch (error) {
    Logger.log('❌ Error en doGet: ' + error.toString());
    return ContentService.createTextOutput(
      JSON.stringify({
        success: false,
        action: 'error',
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

// Función de prueba para verificar que el script funciona
function testUniversalScript() {
  Logger.log('🧪 Iniciando prueba del script universal...');
  
  // Prueba con el spreadsheet actual
  var testSpreadsheetId = '1eSSLrhmiiHt6nQTS24yZ-QFPumMvw0HhdjqxoNd7HGg';
  var testWorksheet = 'BRANDS SHOP';
  
  // Prueba con datos de ejemplo
  var testData = {
    'A1': 'Prueba Universal',
    'B1': '123',
    'C1': 'Test ' + new Date().toISOString()
  };
  
  var result = updateMetrics(testSpreadsheetId, testWorksheet, testData);
  
  if (result) {
    Logger.log('✅ Prueba universal completada exitosamente');
    return 'Prueba exitosa - Script universal funcionando correctamente';
  } else {
    Logger.log('❌ Error en la prueba universal');
    return 'Error en la prueba universal - Revisar configuración';
  }
}

// Función para obtener información del script
function getScriptInfo() {
  return {
    name: 'AdMetricas Universal Web App',
    version: '2.0',
    description: 'Script universal para actualizar cualquier Google Sheet',
    capabilities: [
      'Actualizar cualquier spreadsheet por ID',
      'Listar hojas de cualquier spreadsheet',
      'Verificar acceso a spreadsheets',
      'Manejo dinámico de celdas'
    ],
    timestamp: new Date().toISOString()
  };
}
";
    }

    /**
     * Muestra las instrucciones de configuración
     */
    private function showInstructions($scriptFile, $googleSheet)
    {
        $this->newLine();
        $this->info('📋 INSTRUCCIONES DE CONFIGURACIÓN:');
        $this->newLine();
        
        $this->line('1️⃣ Ve a https://script.google.com/');
        $this->line('2️⃣ Haz clic en "Nuevo proyecto"');
        $this->line('3️⃣ Copia el código del archivo: ' . basename($scriptFile));
        $this->line('4️⃣ Pega el código en el editor');
        $this->line('5️⃣ Guarda el proyecto con un nombre (ej: "AdMetricas WebApp")');
        $this->line('6️⃣ Haz clic en "Implementar" > "Nueva implementación"');
        $this->line('7️⃣ Selecciona "Aplicación web"');
        $this->line('8️⃣ Configura:');
        $this->line('   - Ejecutar como: "Yo mismo"');
        $this->line('   - Acceso: "Cualquier persona"');
        $this->line('9️⃣ Haz clic en "Implementar"');
        $this->line('🔟 Copia la URL del web app');
        
        $this->newLine();
        $this->warn('⚠️  IMPORTANTE: Guarda la URL del web app. Se usará para actualizaciones automáticas.');
        
        $this->newLine();
        $this->info('🧪 PRUEBA:');
        $this->line('1. Haz clic en "Ejecutar" > "testUpdate"');
        $this->line('2. Autoriza el acceso a Google Sheets');
        $this->line('3. Verifica que las celdas se actualizaron');
        
        $this->newLine();
        $this->info('🔗 Una vez configurado, podrás llamar la URL del web app desde Laravel para actualizaciones automáticas.');
    }
}
