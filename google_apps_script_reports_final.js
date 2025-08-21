/**
 * Google Apps Script final para generar reportes estadísticos
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
      }
      
      if (shapes.length > 1) {
        const subtitleShape = shapes[1];
        subtitleShape.getText().setText(description);
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
    
    // Configurar contenido básico
    const shapes = slide.getShapes();
    
    // Configurar título
    if (shapes.length > 0) {
      const titleShape = shapes[0];
      titleShape.getText().setText(slideData.title || 'Diapositiva');
    }
    
    // Configurar subtítulo
    if (shapes.length > 1) {
      const subtitleShape = shapes[1];
      subtitleShape.getText().setText(slideData.subtitle || '');
    }
    
    // Configurar contenido
    if (slideData.content && shapes.length > 2) {
      const contentShape = shapes[2];
      let contentText = '';
      
      // Convertir objeto de contenido a texto
      Object.entries(slideData.content).forEach(([key, value]) => {
        contentText += `${key}: ${value}\n`;
      });
      
      contentShape.getText().setText(contentText);
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
