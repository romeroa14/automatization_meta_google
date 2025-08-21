/**
 * Google Apps Script mejorado para generar reportes estadísticos
 */

function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    const action = data.action;
    
    console.log('Acción recibida:', action);
    
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
    message: 'Google Apps Script para reportes funcionando correctamente',
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

function handleCreatePresentation(data) {
  try {
    const title = data.title || 'Reporte Estadístico';
    const description = data.description || 'Reporte generado automáticamente';
    
    console.log('Creando presentación:', title);
    
    // Crear la presentación
    const presentation = SlidesApp.create(title);
    
    // Obtener la primera diapositiva
    const slides = presentation.getSlides();
    if (slides.length > 0) {
      const firstSlide = slides[0];
      
      // Configurar título y subtítulo
      const shapes = firstSlide.getShapes();
      
      if (shapes.length > 0) {
        const titleShape = shapes[0];
        titleShape.getText().setText(title);
        titleShape.getText().getTextStyle().setFontSize(36).setBold(true);
      }
      
      if (shapes.length > 1) {
        const subtitleShape = shapes[1];
        subtitleShape.getText().setText(description);
        subtitleShape.getText().getTextStyle().setFontSize(18);
      }
    }
    
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
    const presentationId = data.presentation_id;
    const slideIndex = data.slide_index || 0;
    const slideData = data.slide_data;
    
    console.log('Creando diapositiva:', slideIndex, slideData.type);
    
    // Abrir la presentación
    const presentation = SlidesApp.openById(presentationId);
    
    // Crear nueva diapositiva
    const slide = presentation.appendSlide();
    
    // Configurar contenido según el tipo de diapositiva
    switch (slideData.type) {
      case 'cover':
        createCoverSlide(slide, slideData);
        break;
      case 'general_summary':
        createGeneralSummarySlide(slide, slideData);
        break;
      case 'brand_title':
        createBrandTitleSlide(slide, slideData);
        break;
      case 'campaign':
        createCampaignSlide(slide, slideData);
        break;
      case 'chart':
        createChartSlide(slide, slideData);
        break;
      default:
        createGenericSlide(slide, slideData);
    }
    
    console.log('Diapositiva creada exitosamente');
    
    return createSuccessResponse({
      slide_index: slideIndex,
      message: 'Diapositiva creada exitosamente'
    });
    
  } catch (error) {
    console.error('Error creando diapositiva:', error);
    return createErrorResponse('Error creando diapositiva: ' + error.message);
  }
}

function createCoverSlide(slide, data) {
  const shapes = slide.getShapes();
  
  // Configurar título
  if (shapes.length > 0) {
    const titleShape = shapes[0];
    titleShape.getText().setText(data.title);
    titleShape.getText().getTextStyle().setFontSize(36).setBold(true);
  }
  
  // Configurar subtítulo
  if (shapes.length > 1) {
    const subtitleShape = shapes[1];
    subtitleShape.getText().setText(data.subtitle);
    subtitleShape.getText().getTextStyle().setFontSize(18);
  }
  
  // Agregar información adicional si hay espacio
  if (data.content && shapes.length > 2) {
    const contentShape = shapes[2];
    let contentText = '';
    
    if (data.content.description) {
      contentText += data.content.description + '\n\n';
    }
    
    contentText += `📅 Período: ${data.content.total_days} días\n`;
    contentText += `🏷️ Marcas: ${data.content.total_brands}\n`;
    contentText += `📊 Campañas: ${data.content.total_campaigns}`;
    
    contentShape.getText().setText(contentText);
    contentShape.getText().getTextStyle().setFontSize(14);
  }
}

function createGeneralSummarySlide(slide, data) {
  const shapes = slide.getShapes();
  
  // Configurar título
  if (shapes.length > 0) {
    const titleShape = shapes[0];
    titleShape.getText().setText(data.title);
    titleShape.getText().getTextStyle().setFontSize(32).setBold(true);
  }
  
  // Configurar subtítulo
  if (shapes.length > 1) {
    const subtitleShape = shapes[1];
    subtitleShape.getText().setText(data.subtitle);
    subtitleShape.getText().getTextStyle().setFontSize(16);
  }
  
  // Configurar contenido
  if (data.content && shapes.length > 2) {
    const contentShape = shapes[2];
    let contentText = '';
    
    contentText += `📊 Alcance Total: ${data.content.total_reach}\n`;
    contentText += `👁️ Impresiones Totales: ${data.content.total_impressions}\n`;
    contentText += `🖱️ Clicks Totales: ${data.content.total_clicks}\n`;
    contentText += `💰 Gasto Total: ${data.content.total_spend}\n`;
    contentText += `📈 CTR Promedio: ${data.content.average_ctr}\n`;
    contentText += `📊 CPM Promedio: ${data.content.average_cpm}\n`;
    contentText += `🎯 CPC Promedio: ${data.content.average_cpc}\n`;
    contentText += `🏷️ Total Marcas: ${data.content.total_brands}\n`;
    contentText += `📊 Total Campañas: ${data.content.total_campaigns}\n`;
    contentText += `📅 Días del Período: ${data.content.period_days}`;
    
    contentShape.getText().setText(contentText);
    contentShape.getText().getTextStyle().setFontSize(16);
  }
}

function createBrandTitleSlide(slide, data) {
  const shapes = slide.getShapes();
  
  // Configurar título
  if (shapes.length > 0) {
    const titleShape = shapes[0];
    titleShape.getText().setText(data.title);
    titleShape.getText().getTextStyle().setFontSize(32).setBold(true);
  }
  
  // Configurar subtítulo
  if (shapes.length > 1) {
    const subtitleShape = shapes[1];
    subtitleShape.getText().setText(data.subtitle);
    subtitleShape.getText().getTextStyle().setFontSize(16);
  }
  
  // Configurar contenido
  if (data.content && shapes.length > 2) {
    const contentShape = shapes[2];
    let contentText = '';
    
    contentText += `📊 Campañas: ${data.content.total_campaigns}\n`;
    contentText += `👥 Alcance: ${data.content.total_reach}\n`;
    contentText += `👁️ Impresiones: ${data.content.total_impressions}\n`;
    contentText += `🖱️ Clicks: ${data.content.total_clicks}\n`;
    contentText += `💰 Gasto: ${data.content.total_spend}\n`;
    contentText += `📈 CTR Promedio: ${data.content.average_ctr}\n`;
    contentText += `📊 CPM Promedio: ${data.content.average_cpm}\n`;
    contentText += `🎯 CPC Promedio: ${data.content.average_cpc}`;
    
    contentShape.getText().setText(contentText);
    contentShape.getText().getTextStyle().setFontSize(14);
  }
}

function createCampaignSlide(slide, data) {
  const shapes = slide.getShapes();
  
  // Configurar título
  if (shapes.length > 0) {
    const titleShape = shapes[0];
    titleShape.getText().setText(data.title);
    titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
  }
  
  // Configurar subtítulo
  if (shapes.length > 1) {
    const subtitleShape = shapes[1];
    subtitleShape.getText().setText(data.subtitle);
    subtitleShape.getText().getTextStyle().setFontSize(14);
  }
  
  // Configurar contenido
  if (data.content && shapes.length > 2) {
    const contentShape = shapes[2];
    let contentText = '';
    
    contentText += `👥 Alcance: ${data.content.reach}\n`;
    contentText += `👁️ Impresiones: ${data.content.impressions}\n`;
    contentText += `🖱️ Clicks: ${data.content.clicks}\n`;
    contentText += `💰 Gasto: ${data.content.spend}\n`;
    contentText += `📈 CTR: ${data.content.ctr}\n`;
    contentText += `📊 CPM: ${data.content.cpm}\n`;
    contentText += `🎯 CPC: ${data.content.cpc}\n`;
    contentText += `🔄 Frecuencia: ${data.content.frequency}\n`;
    contentText += `💬 Interacciones: ${data.content.total_interactions}\n`;
    contentText += `📊 Tasa Interacción: ${data.content.interaction_rate}\n`;
    contentText += `🎥 Vistas Video: ${data.content.video_views}\n`;
    contentText += `✅ Finalización Video: ${data.content.video_completion_rate}`;
    
    contentShape.getText().setText(contentText);
    contentShape.getText().getTextStyle().setFontSize(12);
  }
  
  // Agregar imagen si existe
  if (data.image && data.image.url) {
    try {
      // Insertar imagen en la diapositiva
      const imageUrl = data.image.url;
      const imageBlob = UrlFetchApp.fetch(imageUrl).getBlob();
      
      // Calcular posición y tamaño de la imagen
      const slideWidth = slide.getWidth();
      const slideHeight = slide.getHeight();
      const imageWidth = slideWidth * 0.4; // 40% del ancho
      const imageHeight = slideHeight * 0.6; // 60% del alto
      const imageX = slideWidth * 0.55; // Posición a la derecha
      const imageY = slideHeight * 0.2; // Posición centrada verticalmente
      
      slide.insertImage(imageBlob, imageX, imageY, imageWidth, imageHeight);
      
    } catch (error) {
      console.warn('Error insertando imagen:', error.message);
    }
  }
}

function createChartSlide(slide, data) {
  const shapes = slide.getShapes();
  
  // Configurar título
  if (shapes.length > 0) {
    const titleShape = shapes[0];
    titleShape.getText().setText(data.title);
    titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
  }
  
  // Configurar subtítulo
  if (shapes.length > 1) {
    const subtitleShape = shapes[1];
    subtitleShape.getText().setText(data.subtitle);
    subtitleShape.getText().getTextStyle().setFontSize(16);
  }
  
  // Configurar contenido con datos de la gráfica
  if (data.content && data.content.chart_data && shapes.length > 2) {
    const contentShape = shapes[2];
    let contentText = `📊 Tipo: ${data.content.chart_type.toUpperCase()}\n`;
    contentText += `📈 Agrupar por: ${data.content.group_by}\n\n`;
    
    // Mostrar datos de la gráfica
    data.content.chart_data.forEach((item, index) => {
      contentText += `${index + 1}. ${item.label}: ${item.formatted_value}\n`;
    });
    
    contentShape.getText().setText(contentText);
    contentShape.getText().getTextStyle().setFontSize(14);
  }
}

function createGenericSlide(slide, data) {
  const shapes = slide.getShapes();
  
  // Configurar título
  if (shapes.length > 0) {
    const titleShape = shapes[0];
    titleShape.getText().setText(data.title || 'Diapositiva');
    titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
  }
  
  // Configurar subtítulo
  if (shapes.length > 1) {
    const subtitleShape = shapes[1];
    subtitleShape.getText().setText(data.subtitle || '');
    subtitleShape.getText().getTextStyle().setFontSize(16);
  }
  
  // Configurar contenido
  if (data.content && shapes.length > 2) {
    const contentShape = shapes[2];
    let contentText = '';
    
    // Convertir objeto de contenido a texto
    Object.entries(data.content).forEach(([key, value]) => {
      contentText += `${key}: ${value}\n`;
    });
    
    contentShape.getText().setText(contentText);
    contentShape.getText().getTextStyle().setFontSize(14);
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
