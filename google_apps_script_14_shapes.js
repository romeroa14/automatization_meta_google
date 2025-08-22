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
    console.log('=== CREANDO DIAPOSITIVA ===');
    
    const presentationId = data.presentation_id;
    const slideData = data.slide_data;
    const slideType = slideData.type || 'ad';
    
    console.log('ID de presentación:', presentationId);
    console.log('Título:', slideData.title);
    console.log('Tipo:', slideType);
    
    const presentation = SlidesApp.openById(presentationId);
    
    // Crear diapositiva en blanco
    const slide = presentation.appendSlide();
    
    switch (slideType) {
      case 'membrete':
        return createMembreteSlide(slide, slideData);
      case 'objetivos':
        return createObjetivosSlide(slide, slideData);
      case 'brand_title':
        return createBrandTitleSlide(slide, slideData);
      case 'ad':
        return createAdSlide(slide, slideData);
      case 'metrics_summary':
        return createMetricsSummarySlide(slide, slideData);
      default:
        return createAdSlide(slide, slideData); // Por defecto
    }
    
  } catch (error) {
    console.error('Error creando diapositiva:', error);
    return createErrorResponse('Error creando diapositiva: ' + error.message);
  }
}

function createMembreteSlide(slide, slideData) {
  // 1. TÍTULO PRINCIPAL
  const titleShape = slide.insertTextBox(slideData.title || 'REPORTE MULTI-CUENTA');
  titleShape.setLeft(50);
  titleShape.setTop(100);
  titleShape.setWidth(700);
  titleShape.setHeight(80);
  titleShape.getText().getTextStyle().setFontSize(32).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // 2. SUBTÍTULO
  const subtitleShape = slide.insertTextBox(slideData.subtitle || '');
  subtitleShape.setLeft(50);
  subtitleShape.setTop(200);
  subtitleShape.setWidth(700);
  subtitleShape.setHeight(60);
  subtitleShape.getText().getTextStyle().setFontSize(20);
  subtitleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: 2,
    message: 'Membrete creado exitosamente'
  });
}

function createObjetivosSlide(slide, slideData) {
  // 1. TÍTULO
  const titleShape = slide.insertTextBox(slideData.title || 'OBJETIVOS DE LAS CAMPAÑAS');
  titleShape.setLeft(50);
  titleShape.setTop(50);
  titleShape.setWidth(700);
  titleShape.setHeight(60);
  titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // 2. CONTENIDO
  const contentShape = slide.insertTextBox('• Análisis de rendimiento de campañas\n• Métricas clave de Facebook Ads\n• Optimización de presupuestos\n• Seguimiento de objetivos');
  contentShape.setLeft(100);
  contentShape.setTop(150);
  contentShape.setWidth(600);
  contentShape.setHeight(200);
  contentShape.getText().getTextStyle().setFontSize(18);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: 2,
    message: 'Slide de objetivos creado exitosamente'
  });
}

function createBrandTitleSlide(slide, slideData) {
  // 1. TÍTULO DE MARCA
  const titleShape = slide.insertTextBox(slideData.title || 'MARCA');
  titleShape.setLeft(50);
  titleShape.setTop(200);
  titleShape.setWidth(700);
  titleShape.setHeight(100);
  titleShape.getText().getTextStyle().setFontSize(36).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // 2. SUBTÍTULO
  const subtitleShape = slide.insertTextBox(slideData.subtitle || 'Campañas y anuncios');
  subtitleShape.setLeft(50);
  subtitleShape.setTop(320);
  subtitleShape.setWidth(700);
  subtitleShape.setHeight(60);
  subtitleShape.getText().getTextStyle().setFontSize(20);
  subtitleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: 2,
    message: 'Título de marca creado exitosamente'
  });
}

function createAdSlide(slide, slideData) {
  // *** CREAR EXACTAMENTE 14 SHAPES ***
  
  // 1. TÍTULO PRINCIPAL
  const titleShape = slide.insertTextBox(slideData.title || 'Reporte de Campaña');
  titleShape.setLeft(50);
  titleShape.setTop(50);
  titleShape.setWidth(700);
  titleShape.setHeight(60);
  titleShape.getText().getTextStyle().setFontSize(24).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
      // 2. ANUNCIO/IMAGEN
    const imageUrl = slideData.image_url;
    const imageLocalPath = slideData.image_local_path;
    
    if (imageUrl) {
      try {
        // Intentar insertar la imagen real del anuncio
        const adImage = slide.insertImage(imageUrl);
        adImage.setLeft(50);
        adImage.setTop(150);
        adImage.setWidth(300);
        adImage.setHeight(200);
        console.log('Imagen del anuncio insertada:', imageUrl);
      } catch (error) {
        console.log('Error insertando imagen, usando placeholder:', error.message);
        // Fallback a placeholder
        const adShape = slide.insertTextBox('📱 ANUNCIO\n' + (slideData.title || 'ESTIVANELI'));
        adShape.setLeft(50);
        adShape.setTop(150);
        adShape.setWidth(300);
        adShape.setHeight(200);
        adShape.getText().getTextStyle().setFontSize(16).setBold(true);
        adShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
        adShape.getBorder().setTransparent();
        adShape.getFill().setSolidFill('#E3F2FD');
      }
    } else {
      // Placeholder si no hay imagen
      const adShape = slide.insertTextBox('📱 ANUNCIO\n' + (slideData.title || 'ESTIVANELI'));
      adShape.setLeft(50);
      adShape.setTop(150);
      adShape.setWidth(300);
      adShape.setHeight(200);
      adShape.getText().getTextStyle().setFontSize(16).setBold(true);
      adShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
      adShape.getBorder().setTransparent();
      adShape.getFill().setSolidFill('#E3F2FD');
    }
  
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
    slide_index: slideData.slide_index || 0,
    shapes_created: 14,
    message: 'Diapositiva de anuncio creada con 14 shapes exitosamente'
  });
}

function createMetricsSummarySlide(slide, slideData) {
  // 1. TÍTULO
  const titleShape = slide.insertTextBox(slideData.title || 'MÉTRICAS POR MARCA');
  titleShape.setLeft(50);
  titleShape.setTop(50);
  titleShape.setWidth(700);
  titleShape.setHeight(60);
  titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // 2. TABLA DE MÉTRICAS POR MARCA
  const brandMetrics = slideData.brand_metrics || {};
  const brands = Object.keys(brandMetrics);
  
  let shapeIndex = 2;
  let yPosition = 150;
  
  for (let i = 0; i < brands.length; i++) {
    const brandName = brands[i];
    const metrics = brandMetrics[brandName];
    
    // Título de marca
    const brandTitleShape = slide.insertTextBox(brandName);
    brandTitleShape.setLeft(50);
    brandTitleShape.setTop(yPosition);
    brandTitleShape.setWidth(200);
    brandTitleShape.setHeight(40);
    brandTitleShape.getText().getTextStyle().setFontSize(16).setBold(true);
    brandTitleShape.getFill().setSolidFill('#E3F2FD');
    
    // Métricas de la marca
    const metricsText = `Alcance: ${metrics.alcance}\nImpresiones: ${metrics.impresiones}\nClicks: ${metrics.clicks}\nCTR: ${metrics.ctr}\nGastado: ${metrics.importe_gastado}`;
    const metricsShape = slide.insertTextBox(metricsText);
    metricsShape.setLeft(300);
    metricsShape.setTop(yPosition);
    metricsShape.setWidth(400);
    metricsShape.setHeight(80);
    metricsShape.getText().getTextStyle().setFontSize(14);
    
    yPosition += 120;
    shapeIndex += 2;
  }
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: shapeIndex,
    message: 'Slide de métricas por marca creado exitosamente'
  });
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
