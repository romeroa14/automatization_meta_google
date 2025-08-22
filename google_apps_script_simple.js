/**
 * Google Apps Script simple y directo para generar reportes
 */

function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    const action = data.action;
    
    console.log('Acción recibida:', action);
    
    switch (action) {
      case 'test':
        return createSuccessResponse({
          message: 'Conexión exitosa',
          action: action,
          timestamp: new Date().toISOString()
        });
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
    message: 'Google Apps Script funcionando correctamente',
    timestamp: new Date().toISOString(),
    version: '3.0.0'
  })).setMimeType(ContentService.MimeType.JSON);
}

function handleCreatePresentation(data) {
  try {
    const title = data.title || 'Presentación de Prueba';
    const description = data.description || 'Descripción de prueba';
    
    console.log('Creando presentación:', title);
    
    // Crear la presentación
    const presentation = SlidesApp.create(title);
    
    // Obtener la primera diapositiva
    const slides = presentation.getSlides();
    if (slides.length > 0) {
      const firstSlide = slides[0];
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
    console.log('Datos de la diapositiva:', JSON.stringify(slideData));
    
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
      titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
    }
    
    // Configurar subtítulo
    if (shapes.length > 1) {
      const subtitleShape = shapes[1];
      subtitleShape.getText().setText(slideData.subtitle || '');
      subtitleShape.getText().getTextStyle().setFontSize(16);
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
      contentShape.getText().getTextStyle().setFontSize(14);
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
