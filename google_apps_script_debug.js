/**
 * Google Apps Script con logs EXTREMADAMENTE detallados
 */

function doPost(e) {
  try {
    console.log('=== INICIO DE DO POST ===');
    console.log('Timestamp:', new Date().toISOString());
    console.log('Datos recibidos (raw):', e.postData.contents);
    
    const data = JSON.parse(e.postData.contents);
    console.log('Datos parseados:', JSON.stringify(data, null, 2));
    
    const action = data.action;
    console.log('Acción solicitada:', action);
    
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
    console.error('Stack trace:', error.stack);
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
    console.log('=== CREANDO PRESENTACIÓN ===');
    console.log('Datos de presentación:', JSON.stringify(data, null, 2));
    
    const title = data.title || 'Presentación de Prueba';
    const description = data.description || 'Descripción de prueba';
    
    console.log('Título:', title);
    console.log('Descripción:', description);
    
    // Crear la presentación
    console.log('Llamando a SlidesApp.create...');
    const presentation = SlidesApp.create(title);
    console.log('Presentación creada exitosamente');
    
    // Obtener la primera diapositiva
    console.log('Obteniendo slides...');
    const slides = presentation.getSlides();
    console.log('Número de slides:', slides.length);
    
    if (slides.length > 0) {
      const firstSlide = slides[0];
      console.log('Obteniendo shapes de la primera slide...');
      const shapes = firstSlide.getShapes();
      console.log('Número de shapes en primera slide:', shapes.length);
      
      // Configurar título en la primera forma
      if (shapes.length > 0) {
        const titleShape = shapes[0];
        console.log('Configurando título en shape 0...');
        titleShape.getText().setText(title);
        titleShape.getText().getTextStyle().setFontSize(36).setBold(true);
        console.log('Título configurado exitosamente');
      }
      
      // Configurar descripción en la segunda forma
      if (shapes.length > 1) {
        const subtitleShape = shapes[1];
        console.log('Configurando descripción en shape 1...');
        subtitleShape.getText().setText(description);
        subtitleShape.getText().getTextStyle().setFontSize(18);
        console.log('Descripción configurada exitosamente');
      }
    }
    
    const presentationId = presentation.getId();
    const presentationUrl = presentation.getUrl();
    
    console.log('ID de presentación:', presentationId);
    console.log('URL de presentación:', presentationUrl);
    console.log('=== PRESENTACIÓN CREADA EXITOSAMENTE ===');
    
    return createSuccessResponse({
      presentation_id: presentationId,
      presentation_url: presentationUrl,
      message: 'Presentación creada exitosamente'
    });
    
  } catch (error) {
    console.error('Error creando presentación:', error);
    console.error('Stack trace:', error.stack);
    return createErrorResponse('Error creando presentación: ' + error.message);
  }
}

function handleCreateSlide(data) {
  try {
    console.log('=== CREANDO DIAPOSITIVA ===');
    console.log('Datos completos:', JSON.stringify(data, null, 2));
    
    const presentationId = data.presentation_id;
    const slideIndex = data.slide_index || 0;
    const slideData = data.slide_data;
    
    console.log('ID de presentación:', presentationId);
    console.log('Índice de diapositiva:', slideIndex);
    console.log('Tipo de diapositiva:', slideData.type);
    console.log('Título:', slideData.title);
    console.log('Subtítulo:', slideData.subtitle);
    console.log('Contenido:', JSON.stringify(slideData.content, null, 2));
    
    // Abrir la presentación
    console.log('Abriendo presentación...');
    const presentation = SlidesApp.openById(presentationId);
    console.log('Presentación abierta exitosamente');
    
    // Crear nueva diapositiva
    console.log('Creando nueva diapositiva...');
    const slide = presentation.appendSlide();
    console.log('Diapositiva creada exitosamente');
    
    // Obtener formas
    console.log('Obteniendo shapes de la nueva diapositiva...');
    const shapes = slide.getShapes();
    console.log('Número de shapes en nueva diapositiva:', shapes.length);
    
    // Configurar título
    if (shapes.length > 0) {
      const titleShape = shapes[0];
      console.log('Configurando título en shape 0...');
      console.log('Texto a insertar:', slideData.title || 'Diapositiva');
      titleShape.getText().setText(slideData.title || 'Diapositiva');
      titleShape.getText().getTextStyle().setFontSize(28).setBold(true);
      console.log('Título configurado exitosamente');
    } else {
      console.warn('No hay shapes disponibles para el título');
    }
    
    // Configurar subtítulo
    if (shapes.length > 1) {
      const subtitleShape = shapes[1];
      console.log('Configurando subtítulo en shape 1...');
      console.log('Texto a insertar:', slideData.subtitle || '');
      subtitleShape.getText().setText(slideData.subtitle || '');
      subtitleShape.getText().getTextStyle().setFontSize(16);
      console.log('Subtítulo configurado exitosamente');
    } else {
      console.warn('No hay shapes disponibles para el subtítulo');
    }
    
    // Configurar contenido
    if (slideData.content && shapes.length > 2) {
      const contentShape = shapes[2];
      console.log('Configurando contenido en shape 2...');
      
      let contentText = '';
      console.log('Procesando contenido...');
      
      if (typeof slideData.content === 'object') {
        Object.entries(slideData.content).forEach(([key, value]) => {
          const line = `${key}: ${value}`;
          contentText += line + '\n';
          console.log('Agregando línea:', line);
        });
      } else {
        contentText = String(slideData.content);
        console.log('Contenido no es objeto, usando como string:', contentText);
      }
      
      console.log('Texto final del contenido:', contentText);
      contentShape.getText().setText(contentText);
      contentShape.getText().getTextStyle().setFontSize(14);
      console.log('Contenido configurado exitosamente');
    } else {
      console.warn('No hay contenido o no hay shapes disponibles para el contenido');
      console.log('slideData.content existe:', !!slideData.content);
      console.log('shapes.length > 2:', shapes.length > 2);
    }
    
    console.log('=== DIAPOSITIVA CREADA EXITOSAMENTE ===');
    
    return createSuccessResponse({
      slide_index: slideIndex,
      message: 'Diapositiva creada exitosamente'
    });
    
  } catch (error) {
    console.error('Error creando diapositiva:', error);
    console.error('Stack trace:', error.stack);
    return createErrorResponse('Error creando diapositiva: ' + error.message);
  }
}

function createSuccessResponse(data) {
  console.log('Enviando respuesta exitosa:', JSON.stringify(data, null, 2));
  return ContentService.createTextOutput(JSON.stringify({
    success: true,
    data: data,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

function createErrorResponse(message) {
  console.error('Enviando respuesta de error:', message);
  return ContentService.createTextOutput(JSON.stringify({
    success: false,
    error: message,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}
