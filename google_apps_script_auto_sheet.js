// Google Apps Script Web App Avanzado para Google Sheets
// Funcionalidades: Actualizar celdas, listar hojas, crear hojas automáticas
// Copia este código en https://script.google.com/

/**
 * Función principal para manejar todas las acciones
 */
function doGet(e) {
  try {
    var params = e.parameter;
    var action = params.action || 'update';
    
    Logger.log('=== INICIO DE ACCIÓN ===');
    Logger.log('Acción solicitada: ' + action);
    Logger.log('Parámetros recibidos: ' + JSON.stringify(params));
    
    var result;
    
    switch(action) {
      case 'list_sheets':
        result = listWorksheets(params.spreadsheet_id);
        break;
      case 'update':
        result = updateMetrics(params.worksheet || 'BRANDS SHOP', params.updates);
        break;
      case 'create_sheet':
        result = createAutoSheet(params.spreadsheet_id, params.sheet_name, params.data);
        break;
      default:
        throw new Error('Acción no reconocida: ' + action);
    }
    
    Logger.log('✅ Acción completada exitosamente');
    return ContentService.createTextOutput(JSON.stringify(result))
      .setMimeType(ContentService.MimeType.JSON);
      
  } catch (error) {
    Logger.log('❌ Error en doGet: ' + error.toString());
    return ContentService.createTextOutput(
      JSON.stringify({ 
        success: false, 
        message: 'Error: ' + error.toString(), 
        timestamp: new Date().toISOString() 
      })
    ).setMimeType(ContentService.MimeType.JSON);
  }
}

/**
 * Función para POST (más seguro)
 */
function doPost(e) {
  return doGet(e);
}

/**
 * Actualiza métricas en una hoja específica
 */
function updateMetrics(worksheetName, updatesData) {
  try {
    var spreadsheetId = '1eSSLrhmiiHt6nQTS24yZ-QFPumMvw0HhdjqxoNd7HGg';
    var worksheetName = worksheetName || 'BRANDS SHOP';
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    var sheet = spreadsheet.getSheetByName(worksheetName);
    
    if (!sheet) {
      Logger.log('Error: Hoja no encontrada: ' + worksheetName);
      return { success: false, message: 'Hoja no encontrada: ' + worksheetName };
    }

    // Datos a actualizar (se pasan como parámetros desde Laravel)
    var updates = updatesData || {};
    
    // Si updates es un string JSON, parsearlo
    if (typeof updates === 'string') {
      try {
        updates = JSON.parse(updates);
      } catch (e) {
        Logger.log('Error parseando JSON: ' + e);
        return { success: false, message: 'Error parseando JSON: ' + e };
      }
    }

    // Validar que hay datos para actualizar
    if (Object.keys(updates).length === 0) {
      Logger.log('No hay datos para actualizar');
      return { success: false, message: 'No hay datos para actualizar' };
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

    Logger.log('Se actualizaron ' + updatedCount + ' celdas exitosamente en la hoja: ' + worksheetName);
    
    if (errors.length > 0) {
      Logger.log('Errores encontrados: ' + errors.join(', '));
    }

    return {
      success: updatedCount > 0,
      message: updatedCount > 0 ? 
        'Google Sheet actualizado exitosamente en la hoja: ' + worksheetName :
        'No se pudieron actualizar las celdas',
      worksheet: worksheetName,
      timestamp: new Date().toISOString(),
      updated_cells: updatedCount,
      errors: errors
    };
    
  } catch (error) {
    Logger.log('Error general: ' + error.toString());
    return { 
      success: false, 
      message: 'Error actualizando Google Sheet en la hoja: ' + worksheetName,
      error: error.toString()
    };
  }
}

/**
 * Lista todas las hojas disponibles
 */
function listWorksheets(spreadsheetId) {
  try {
    var spreadsheetId = spreadsheetId || '1eSSLrhmiiHt6nQTS24yZ-QFPumMvw0HhdjqxoNd7HGg';
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    var sheets = spreadsheet.getSheets();
    var sheetNames = [];
    
    for (var i = 0; i < sheets.length; i++) {
      sheetNames.push(sheets[i].getName());
    }
    
    Logger.log('Hojas disponibles: ' + sheetNames.join(', '));
    
    return {
      success: true,
      sheets: sheetNames,
      count: sheetNames.length,
      timestamp: new Date().toISOString()
    };
    
  } catch (error) {
    Logger.log('Error listando hojas: ' + error);
    return { 
      success: false, 
      message: 'Error listando hojas: ' + error.toString(),
      sheets: []
    };
  }
}

/**
 * Crea una hoja automática con estadísticas completas
 */
function createAutoSheet(spreadsheetId, sheetName, data) {
  try {
    Logger.log('=== CREANDO HOJA AUTOMÁTICA ===');
    Logger.log('Spreadsheet ID: ' + spreadsheetId);
    Logger.log('Nombre de hoja: ' + sheetName);
    
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    
    // Verificar si la hoja ya existe
    var existingSheet = spreadsheet.getSheetByName(sheetName);
    if (existingSheet) {
      Logger.log('Hoja existente encontrada, eliminando...');
      spreadsheet.deleteSheet(existingSheet);
    }
    
    // Crear nueva hoja
    var newSheet = spreadsheet.insertSheet(sheetName);
    Logger.log('Nueva hoja creada: ' + sheetName);
    
    // Parsear datos si es string
    if (typeof data === 'string') {
      data = JSON.parse(data);
    }
    
    // Aplicar formato y datos
    var result = applyAutoSheetFormat(newSheet, data);
    
    Logger.log('✅ Hoja automática creada exitosamente');
    
    return {
      success: true,
      message: 'Hoja automática "' + sheetName + '" creada exitosamente',
      sheet_name: sheetName,
      spreadsheet_id: spreadsheetId,
      timestamp: new Date().toISOString(),
      details: result
    };
    
  } catch (error) {
    Logger.log('❌ Error creando hoja automática: ' + error.toString());
    return {
      success: false,
      message: 'Error creando hoja automática: ' + error.toString(),
      timestamp: new Date().toISOString()
    };
  }
}

/**
 * Aplica formato y datos a la hoja automática
 */
function applyAutoSheetFormat(sheet, data) {
  try {
    Logger.log('Aplicando formato y datos...');
    
    var row = 1;
    var maxCol = 1;
    var updatedCells = 0;
    
    // Procesar cada fila de datos
    for (var i = 0; i < data.length; i++) {
      var rowData = data[i];
      
      // Encontrar la columna máxima para esta fila
      var colCount = Object.keys(rowData).length;
      if (colCount > maxCol) {
        maxCol = colCount;
      }
      
      // Aplicar datos de la fila
      for (var cell in rowData) {
        try {
          var value = rowData[cell];
          sheet.getRange(cell).setValue(value);
          updatedCells++;
          
          // Aplicar formato especial según el contenido
          applyCellFormat(sheet, cell, value);
          
        } catch (error) {
          Logger.log('Error aplicando celda ' + cell + ': ' + error);
        }
      }
      
      row++;
    }
    
    // Aplicar formato general
    applyGeneralFormat(sheet, maxCol, row - 1);
    
    Logger.log('Formato aplicado: ' + updatedCells + ' celdas actualizadas');
    
    return {
      cells_updated: updatedCells,
      max_columns: maxCol,
      max_rows: row - 1
    };
    
  } catch (error) {
    Logger.log('Error aplicando formato: ' + error.toString());
    throw error;
  }
}

/**
 * Aplica formato específico a una celda
 */
function applyCellFormat(sheet, cell, value) {
  try {
    var range = sheet.getRange(cell);
    
    // Formato para encabezados principales
    if (cell.includes('1') && (value.includes('📊') || value.includes('🎯'))) {
      range.setFontWeight('bold');
      range.setFontSize(14);
      range.setBackground('#4285f4');
      range.setFontColor('white');
      range.setHorizontalAlignment('center');
    }
    
    // Formato para encabezados de columnas
    else if (value.includes('📸') || value.includes('ID') || value.includes('Impresiones') || 
             value.includes('Alcance') || value.includes('Clicks') || value.includes('Gasto') ||
             value.includes('CTR') || value.includes('Interacciones') || value.includes('Tasa') ||
             value.includes('Videos') || value.includes('CPM') || value.includes('CPC') ||
             value.includes('Título')) {
      range.setFontWeight('bold');
      range.setBackground('#f8f9fa');
      range.setBorder(true, true, true, true, true, true);
      range.setHorizontalAlignment('center');
    }
    
    // Formato para valores monetarios
    else if (typeof value === 'string' && value.includes('$')) {
      range.setNumberFormat('$#,##0.00');
      range.setHorizontalAlignment('right');
    }
    
    // Formato para porcentajes
    else if (typeof value === 'string' && value.includes('%')) {
      range.setNumberFormat('0.00%');
      range.setHorizontalAlignment('center');
    }
    
    // Formato para números grandes
    else if (typeof value === 'string' && /^\d{1,3}(,\d{3})*$/.test(value.replace(/[^\d,]/g, ''))) {
      range.setNumberFormat('#,##0');
      range.setHorizontalAlignment('right');
    }
    
    // Formato para IDs
    else if (typeof value === 'string' && value.length > 10 && /^\d+$/.test(value)) {
      range.setFontFamily('Courier New');
      range.setFontSize(10);
      range.setHorizontalAlignment('left');
    }
    
  } catch (error) {
    Logger.log('Error aplicando formato a celda ' + cell + ': ' + error);
  }
}

/**
 * Aplica formato general a la hoja
 */
function applyGeneralFormat(sheet, maxCol, maxRow) {
  try {
    // Ajustar ancho de columnas automáticamente
    for (var col = 1; col <= maxCol; col++) {
      sheet.autoResizeColumn(col);
    }
    
    // Aplicar bordes a toda la tabla
    var dataRange = sheet.getRange(1, 1, maxRow, maxCol);
    dataRange.setBorder(true, true, true, true, true, true);
    
    // Congelar primera fila
    sheet.setFrozenRows(1);
    
    // Aplicar filtros
    if (maxRow > 1) {
      sheet.getRange(1, 1, maxRow, maxCol).createFilter();
    }
    
    Logger.log('Formato general aplicado: ' + maxCol + ' columnas, ' + maxRow + ' filas');
    
  } catch (error) {
    Logger.log('Error aplicando formato general: ' + error);
  }
}

/**
 * Función de prueba para verificar que el script funciona
 */
function testCreateAutoSheet() {
  Logger.log('🧪 Iniciando prueba de creación de hoja automática...');
  
  var testData = [
    {
      'A1': '📊 REPORTE DE ANUNCIOS FACEBOOK',
      'B1': 'Generado: ' + new Date().toISOString()
    },
    {
      'A3': '🎯 CAMPAÑA: Test Campaign',
      'B3': 'ID: 123456789',
      'C3': 'Estado: ACTIVE'
    },
    {
      'A4': '📸 Anuncio',
      'B4': 'ID',
      'C4': 'Impresiones',
      'D4': 'Clicks',
      'E4': 'Gasto ($)',
      'F4': 'CTR (%)'
    },
    {
      'A5': 'Combo Test',
      'B5': '120231341075110153',
      'C5': '3,617',
      'D5': '208',
      'E5': '$2.73',
      'F5': '5.75%'
    }
  ];
  
  var result = createAutoSheet('1eSSLrhmiiHt6nQTS24yZ-QFPumMvw0HhdjqxoNd7HGg', 'Prueba Automática', testData);
  
  if (result.success) {
    Logger.log('✅ Prueba completada exitosamente');
    return 'Prueba exitosa - Hoja automática creada correctamente';
  } else {
    Logger.log('❌ Error en la prueba');
    return 'Error en la prueba - ' + result.message;
  }
}

/**
 * Función para verificar permisos y configuración
 */
function checkConfiguration() {
  try {
    Logger.log('🔧 Verificando configuración...');
    
    var spreadsheetId = '1eSSLrhmiiHt6nQTS24yZ-QFPumMvw0HhdjqxoNd7HGg';
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    
    Logger.log('✅ Spreadsheet accesible: ' + spreadsheet.getName());
    Logger.log('✅ ID del spreadsheet: ' + spreadsheetId);
    
    var sheets = listWorksheets(spreadsheetId);
    Logger.log('✅ Hojas encontradas: ' + sheets.count);
    
    return {
      success: true,
      spreadsheet_name: spreadsheet.getName(),
      sheets_count: sheets.count,
      sheets: sheets.sheets,
      permissions: 'OK'
    };
    
  } catch (error) {
    Logger.log('❌ Error en configuración: ' + error);
    return {
      success: false,
      error: error.toString()
    };
  }
}
