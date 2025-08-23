/**
 * Google Apps Script - Corregido para manejar diferentes tipos de slides
 * Procesa correctamente los datos de Facebook y crea las diapositivas en el orden correcto
 */

function doPost(e) {
  try {
    console.log('=== INICIO DE DO POST ===');
    
    const data = JSON.parse(e.postData.contents);
    const action = data.action;
    
    console.log('Acción:', action);
    console.log('Datos recibidos:', JSON.stringify(data, null, 2));
    
    switch (action) {
      case 'create_presentation':
        return handleCreatePresentation(data);
      case 'create_slide':
        return handleCreateSlide(data);
      case 'create_multiple_slides':
        return handleCreateMultipleSlides(data);
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

function handleCreateMultipleSlides(data) {
  try {
    console.log('=== CREANDO MÚLTIPLES DIAPOSITIVAS ===');
    
    const presentationId = data.presentation_id;
    const slides = data.slides || [];
    
    console.log('ID de presentación:', presentationId);
    console.log('Total de slides a crear:', slides.length);
    
    const presentation = SlidesApp.openById(presentationId);
    const results = [];
    
    for (let i = 0; i < slides.length; i++) {
      const slideData = slides[i];
      console.log(`\n--- Creando slide ${i + 1}/${slides.length} ---`);
      console.log('Tipo:', slideData.type);
      console.log('Título:', slideData.title);
      
      // Crear diapositiva en blanco
      const slide = presentation.appendSlide();
      
      let result;
      switch (slideData.type) {
        case 'title':
          result = createTitleSlide(slide, slideData);
          break;
        case 'content':
          result = createContentSlide(slide, slideData);
          break;
        case 'brand_title':
          result = createBrandTitleSlide(slide, slideData);
          break;
        case 'ad':
          result = createAdSlide(slide, slideData);
          break;
        case 'metrics_summary':
          result = createMetricsSummarySlide(slide, slideData);
          break;
        default:
          console.log('Tipo no reconocido, usando slide de anuncio por defecto');
          result = createAdSlide(slide, slideData);
      }
      
      results.push({
        slide_index: i,
        type: slideData.type,
        title: slideData.title,
        result: result
      });
      
      console.log(`✅ Slide ${i + 1} creado exitosamente`);
    }
    
    console.log('=== TODAS LAS DIAPOSITIVAS CREADAS ===');
    console.log('Total creadas:', results.length);
    
    return createSuccessResponse({
      presentation_id: presentationId,
      slides_created: results.length,
      results: results,
      message: `Se crearon ${results.length} diapositivas exitosamente`
    });
    
  } catch (error) {
    console.error('Error creando múltiples diapositivas:', error);
    return createErrorResponse('Error creando múltiples diapositivas: ' + error.message);
  }
}

function handleCreateSlide(data) {
  try {
    console.log('=== CREANDO DIAPOSITIVA INDIVIDUAL ===');
    
    const presentationId = data.presentation_id;
    const slideData = data.slide_data;
    const slideType = slideData.type || 'ad';
    
    console.log('ID de presentación:', presentationId);
    console.log('Título:', slideData.title);
    console.log('Tipo:', slideType);
    console.log('Datos completos:', JSON.stringify(slideData, null, 2));
    
    const presentation = SlidesApp.openById(presentationId);
    
    // Crear diapositiva en blanco
    const slide = presentation.appendSlide();
    
    switch (slideType) {
      case 'title':
        return createTitleSlide(slide, slideData);
      case 'content':
        return createContentSlide(slide, slideData);
      case 'brand_title':
        return createBrandTitleSlide(slide, slideData);
      case 'ad':
        return createAdSlide(slide, slideData);
      case 'metrics_summary':
        return createMetricsSummarySlide(slide, slideData);
      default:
        console.log('Tipo no reconocido, usando slide de anuncio por defecto');
        return createAdSlide(slide, slideData);
    }
    
  } catch (error) {
    console.error('Error creando diapositiva:', error);
    return createErrorResponse('Error creando diapositiva: ' + error.message);
  }
}

function createTitleSlide(slide, slideData) {
  console.log('Creando slide de título...');
  
  const title = slideData.title || 'Título del Reporte';
  const subtitle = slideData.subtitle || 'Subtítulo del Reporte';
  
  // Título principal
  const titleShape = slide.insertTextBox(title);
  titleShape.setLeft(100);
  titleShape.setTop(200);
  titleShape.setWidth(600);
  titleShape.setHeight(100);
  titleShape.getText().getTextStyle().setFontSize(36).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // Subtítulo
  const subtitleShape = slide.insertTextBox(subtitle);
  subtitleShape.setLeft(100);
  subtitleShape.setTop(350);
  subtitleShape.setWidth(600);
  subtitleShape.setHeight(50);
  subtitleShape.getText().getTextStyle().setFontSize(18);
  subtitleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: 2,
    message: 'Slide de título creado exitosamente'
  });
}

function createContentSlide(slide, slideData) {
  console.log('Creando slide de contenido...');
  
  const title = slideData.title || 'Contenido';
  const content = slideData.content || {};
  
  // Título
  const titleShape = slide.insertTextBox(title);
  titleShape.setLeft(50);
  titleShape.setTop(50);
  titleShape.setWidth(700);
  titleShape.setHeight(60);
  titleShape.getText().getTextStyle().setFontSize(24).setBold(true);
  
  // Contenido
  let contentText = '';
  for (const [key, value] of Object.entries(content)) {
    contentText += `${key}: ${value}\n`;
  }
  
  const contentShape = slide.insertTextBox(contentText);
  contentShape.setLeft(50);
  contentShape.setTop(150);
  contentShape.setWidth(700);
  contentShape.setHeight(400);
  contentShape.getText().getTextStyle().setFontSize(16);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: 2,
    message: 'Slide de contenido creado exitosamente'
  });
}

function createBrandTitleSlide(slide, slideData) {
  console.log('Creando slide de título de marca...');
  
  const title = slideData.title || 'Marca';
  const subtitle = slideData.subtitle || 'Resumen de Anuncios';
  const metrics = slideData.metrics || {};
  
  // Título principal
  const titleShape = slide.insertTextBox(title);
  titleShape.setLeft(50);
  titleShape.setTop(50);
  titleShape.setWidth(700);
  titleShape.setHeight(60);
  titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
  titleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // Subtítulo
  const subtitleShape = slide.insertTextBox(subtitle);
  subtitleShape.setLeft(50);
  subtitleShape.setTop(120);
  subtitleShape.setWidth(700);
  subtitleShape.setHeight(40);
  subtitleShape.getText().getTextStyle().setFontSize(18);
  subtitleShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
  
  // Métricas
  let metricsText = '';
  for (const [key, value] of Object.entries(metrics)) {
    metricsText += `${key}: ${value}\n`;
  }
  
  const metricsShape = slide.insertTextBox(metricsText);
  metricsShape.setLeft(50);
  metricsShape.setTop(200);
  metricsShape.setWidth(600);
  metricsShape.setHeight(150);
  metricsShape.getText().getTextStyle().setFontSize(14);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: slideData.subtitle ? (slideData.metrics ? 3 : 2) : 1,
    message: 'Título de marca creado exitosamente'
  });
}

function createAdSlide(slide, slideData) {
  console.log('Creando slide de anuncio con datos:', JSON.stringify(slideData.metrics, null, 2));
  
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
  console.log('Procesando imagen del anuncio:', slideData.ad_image_url);
  
  if (slideData.ad_image_url) {
    try {
      // Intentar insertar imagen del anuncio
      console.log('Intentando insertar imagen desde:', slideData.ad_image_url);
      const imageShape = slide.insertImage(slideData.ad_image_url);
      imageShape.setLeft(50);
      imageShape.setTop(150);
      imageShape.setWidth(300);
      imageShape.setHeight(200);
      console.log('✅ Imagen del anuncio insertada exitosamente');
    } catch (error) {
      console.error('❌ Error insertando imagen:', error.message);
      console.log('🔧 Usando fallback con texto...');
      
      // Fallback: crear shape con texto y mostrar URL de la imagen
      const adShape = slide.insertTextBox('📱 ANUNCIO\n' + (slideData.title || 'ANUNCIO') + '\n\n🖼️ Imagen disponible\n(Ver logs para detalles)');
      adShape.setLeft(50);
      adShape.setTop(150);
      adShape.setWidth(300);
      adShape.setHeight(200);
      adShape.getText().getTextStyle().setFontSize(14).setBold(true);
      adShape.getText().getParagraphStyle().setParagraphAlignment(SlidesApp.ParagraphAlignment.CENTER);
      adShape.getBorder().setTransparent();
      adShape.getFill().setSolidFill('#E3F2FD');
    }
  } else {
    console.log('⚠️ No hay URL de imagen disponible');
    // Crear shape con texto si no hay imagen
    const adShape = slide.insertTextBox('📱 ANUNCIO\n' + (slideData.title || 'ANUNCIO') + '\n\n❌ Sin imagen');
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
    
    console.log(`Shape ${shapeIndex}: ${metric.label} = ${metric.value}`);
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
  console.log('Creando slide de resumen de métricas...');
  
  const title = slideData.title || 'Resumen de Métricas';
  const content = slideData.content || {};
  
  // Título
  const titleShape = slide.insertTextBox(title);
  titleShape.setLeft(50);
  titleShape.setTop(50);
  titleShape.setWidth(700);
  titleShape.setHeight(60);
  titleShape.getText().getTextStyle().setFontSize(24).setBold(true);
  
  // Contenido
  let contentText = '';
  for (const [key, value] of Object.entries(content)) {
    contentText += `${key}: ${value}\n`;
  }
  
  const contentShape = slide.insertTextBox(contentText);
  contentShape.setLeft(50);
  contentShape.setTop(150);
  contentShape.setWidth(700);
  contentShape.setHeight(400);
  contentShape.getText().getTextStyle().setFontSize(16);
  
  return createSuccessResponse({
    slide_index: slideData.slide_index || 0,
    shapes_created: 2,
    message: 'Slide de resumen de métricas creado exitosamente'
  });
}

function createSuccessResponse(data) {
  return ContentService.createTextOutput(JSON.stringify({
    status: 'success',
    data: data,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

function createErrorResponse(message) {
  return ContentService.createTextOutput(JSON.stringify({
    status: 'error',
    message: message,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}
