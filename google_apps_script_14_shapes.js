/**
 * Google Apps Script - 14 Shapes para Reporte de Facebook Ads
 * Crea exactamente 14 shapes organizados como en el reporte de Facebook
 */

function doPost(e) {
  try {
    console.log('=== INICIO DE DO POST ===');
    
    const data = JSON.parse(e.postData.contents);
    const action = data.action;
    
    console.log('Acción:', action);
    
    switch (action) {
      case 'create_presentation':
        return handleCreatePresentation(data);
      case 'create_slide':
        return handleCreateSlide(data);
      default:
        return createErrorResponse('Acción no válida: ' + action);
    }
    
  } catch (error) {
    console.error('Error en doPost:', error);
    return createErrorResponse('Error interno: ' + error.message);
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: 'success',
    message: 'Google Apps Script funcionando',
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

function handleCreatePresentation(data) {
  try {
    console.log('Creando presentación...');
    
    const title = data.title || 'Reporte Multi-Cuenta';
    const presentation = SlidesApp.create(title);
    
    const presentationId = presentation.getId();
    const presentationUrl = presentation.getUrl();
    
    console.log('Presentación creada:', presentationId);
    
    return createSuccessResponse({
      presentation_id: presentationId,
      presentation_url: presentationUrl,
      message: 'Presentación creada exitosamente'
    });
    
  } catch (error) {
    console.error('Error creando presentación:', error);
    return createErrorResponse('Error creando presentación: ' + error.message);
  }
}

function handleCreateSlide(data) {
  try {
    console.log('=== CREANDO DIAPOSITIVA CON 14 SHAPES ===');
    
    const presentationId = data.presentation_id;
    const slideData = data.slide_data;
    
    console.log('ID de presentación:', presentationId);
    console.log('Título:', slideData.title);
    
    const presentation = SlidesApp.openById(presentationId);
    
    // Crear diapositiva en blanco
    const slide = presentation.appendSlide();
    
    // *** CREAR EXACTAMENTE 14 SHAPES ***
    
    // 1. TÍTULO PRINCIPAL
    const titleShape = slide.insertTextBox(slideData.title || 'Reporte de Campaña');
    titleShape.setLeft(50);
    titleShape.setTop(50);
    titleShape.setWidth(700);
    titleShape.setHeight(60);
    titleShape.getText().getTextStyle().setFontSize(24).setBold(true);
    titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
    
    // 2. ANUNCIO/IMAGEN (placeholder)
    const adShape = slide.insertTextBox('📱 ANUNCIO\nESTIVANELI\nCHAQUETAS DE JEANS');
    adShape.setLeft(50);
    adShape.setTop(150);
    adShape.setWidth(300);
    adShape.setHeight(200);
    adShape.getText().getTextStyle().setFontSize(16).setBold(true);
    adShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
    adShape.getBorder().setTransparent();
    adShape.getFill().setSolidFill('#E3F2FD');
    
    // 3-14. MÉTRICAS EN TABLA (12 shapes)
    const metrics = slideData.metrics || {};
    
    // Posiciones para la tabla de métricas
    const tableLeft = 400;
    const tableTop = 150;
    const cellWidth = 150;
    const cellHeight = 40;
    
    // Definir las métricas en el orden correcto
    const metricDefinitions = [
      { key: 'alcance', label: 'Alcance Total', value: metrics.alcance || '0' },
      { key: 'impresiones', label: 'Impresiones', value: metrics.impresiones || '0' },
      { key: 'frecuencia', label: 'Frecuencia', value: metrics.frecuencia || '0' },
      { key: 'clicks', label: 'Clics en el Enlace', value: metrics.clicks || '0' },
      { key: 'ctr', label: 'CTR', value: metrics.ctr || '0%' },
      { key: 'costo_por_resultado', label: 'Coste por resultado', value: metrics.costo_por_resultado || '$0' },
      { key: 'importe_gastado', label: 'Importe Gastado', value: metrics.importe_gastado || '$0' },
      { key: 'resultados', label: 'Resultados', value: metrics.resultados || '0' },
      { key: 'cpm', label: 'CPM', value: metrics.cpm || '$0' },
      { key: 'cpc', label: 'CPC', value: metrics.cpc || '$0' },
      { key: 'frecuencia_media', label: 'Frecuencia Media', value: metrics.frecuencia_media || '0' },
      { key: 'alcance_neto', label: 'Alcance Neto', value: metrics.alcance_neto || '0' }
    ];
    
    let shapeIndex = 3; // Empezamos en el shape #3
    
    for (let i = 0; i < metricDefinitions.length; i++) {
      const metric = metricDefinitions[i];
      const row = Math.floor(i / 2);
      const col = i % 2;
      
      const shape = slide.insertTextBox(`${metric.label}: ${metric.value}`);
      shape.setLeft(tableLeft + (col * cellWidth));
      shape.setTop(tableTop + (row * cellHeight));
      shape.setWidth(cellWidth);
      shape.setHeight(cellHeight);
      
      // Estilo alternado para las filas
      if (row % 2 === 0) {
        shape.getFill().setSolidFill('#F5F5F5'); // Gris claro
      } else {
        shape.getFill().setSolidFill('#E0E0E0'); // Gris oscuro
      }
      
      shape.getText().getTextStyle().setFontSize(12);
      shape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
      shape.getBorder().setTransparent();
      
      console.log(`Shape ${shapeIndex}: ${metric.label}`);
      shapeIndex++;
    }
    
    console.log('=== 14 SHAPES CREADOS EXITOSAMENTE ===');
    
    return createSuccessResponse({
      slide_index: data.slide_index || 0,
      shapes_created: 14,
      message: 'Diapositiva creada con 14 shapes exitosamente'
    });
    
  } catch (error) {
    console.error('Error creando diapositiva:', error);
    return createErrorResponse('Error creando diapositiva: ' + error.message);
  }
}

function createSuccessResponse(data) {
  return ContentService.createTextOutput(JSON.stringify({
    success: true,
    data: data,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

function createErrorResponse(message) {
  return ContentService.createTextOutput(JSON.stringify({
    success: false,
    error: message,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}
