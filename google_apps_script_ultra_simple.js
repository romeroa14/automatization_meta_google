/**
 * Google Apps Script ULTRA simple para diagnosticar problemas
 */

function doPost(e) {
  try {
    console.log('=== INICIO DE DO POST ===');
    console.log('Datos recibidos:', e.postData.contents);
    
    const data = JSON.parse(e.postData.contents);
    const action = data.action;
    
    console.log('Acción:', action);
    
    switch (action) {
      case 'create_presentation':
        return handleCreatePresentation(data);
      case 'create_slide':
        return handleCreateSlide(data);
      default:
        console.log('Acción no válida:', action);
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
    
    const title = data.title || 'Presentación de Prueba';
    console.log('Título:', title);
    
    // Crear la presentación
    const presentation = SlidesApp.create(title);
    console.log('Presentación creada');
    
    // Obtener la primera diapositiva
    const slides = presentation.getSlides();
    console.log('Número de slides:', slides.length);
    
    if (slides.length > 0) {
      const firstSlide = slides[0];
      const shapes = firstSlide.getShapes();
      console.log('Número de shapes en primera slide:', shapes.length);
      
      // Configurar título en la primera forma
      if (shapes.length > 0) {
        const titleShape = shapes[0];
        console.log('Configurando título...');
        titleShape.getText().setText(title);
        titleShape.getText().getTextStyle().setFontSize(36).setBold(true);
        console.log('Título configurado');
      }
    }
    
    const presentationId = presentation.getId();
    const presentationUrl = presentation.getUrl();
    
    console.log('ID de presentación:', presentationId);
    console.log('URL de presentación:', presentationUrl);
    
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
    console.log('Datos de diapositiva:', JSON.stringify(data));
    
    const presentationId = data.presentation_id;
    const slideData = data.slide_data;
    
    console.log('ID de presentación:', presentationId);
    console.log('Tipo de diapositiva:', slideData.type);
    
    // Abrir la presentación
    const presentation = SlidesApp.openById(presentationId);
    console.log('Presentación abierta');
    
    // Crear nueva diapositiva
    const slide = presentation.appendSlide();
    console.log('Diapositiva creada');
    
    // Obtener formas
    const shapes = slide.getShapes();
    console.log('Número de shapes en nueva diapositiva:', shapes.length);
    
    // Configurar título
    if (shapes.length > 0) {
      const titleShape = shapes[0];
      console.log('Configurando título...');
      titleShape.getText().setText(slideData.title || 'Diapositiva');
      titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
      console.log('Título configurado:', slideData.title);
    }
    
    // Configurar subtítulo
    if (shapes.length > 1) {
      const subtitleShape = shapes[1];
      console.log('Configurando subtítulo...');
      subtitleShape.getText().setText(slideData.subtitle || '');
      subtitleShape.getText().getTextStyle().setFontSize(16);
      console.log('Subtítulo configurado:', slideData.subtitle);
    }
    
    // Configurar contenido
    if (slideData.content && shapes.length > 2) {
      const contentShape = shapes[2];
      console.log('Configurando contenido...');
      
      let contentText = '';
      Object.entries(slideData.content).forEach(([key, value]) => {
        contentText += `${key}: ${value}\n`;
      });
      
      contentShape.getText().setText(contentText);
      contentShape.getText().getTextStyle().setFontSize(14);
      console.log('Contenido configurado:', contentText);
    }
    
    console.log('=== DIAPOSITIVA CREADA EXITOSAMENTE ===');
    
    return createSuccessResponse({
      slide_index: data.slide_index || 0,
      message: 'Diapositiva creada exitosamente'
    });
    
  } catch (error) {
    console.error('Error creando diapositiva:', error);
    return createErrorResponse('Error creando diapositiva: ' + error.message);
  }
}

function createSuccessResponse(data) {
  console.log('Respuesta exitosa:', JSON.stringify(data));
  return ContentService.createTextOutput(JSON.stringify({
    success: true,
    data: data,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

function createErrorResponse(message) {
  console.error('Respuesta de error:', message);
  return ContentService.createTextOutput(JSON.stringify({
    success: false,
    error: message,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}
