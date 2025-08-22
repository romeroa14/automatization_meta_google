/**
 * Google Apps Script SIMPLE y CORREGIDO
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
    
    const title = data.title || 'Presentación de Prueba';
    const description = data.description || 'Descripción de prueba';
    
    const presentation = SlidesApp.create(title);
    const slides = presentation.getSlides();
    
    if (slides.length > 0) {
      const firstSlide = slides[0];
      const shapes = firstSlide.getShapes();
      
      if (shapes.length > 0) {
        shapes[0].getText().setText(title);
        shapes[0].getText().getTextStyle().setFontSize(36).setBold(true);
      }
      
      if (shapes.length > 1) {
        shapes[1].getText().setText(description);
        shapes[1].getText().getTextStyle().setFontSize(18);
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
    console.log('=== CREANDO DIAPOSITIVA ===');
    
    const presentationId = data.presentation_id;
    const slideData = data.slide_data;
    
    console.log('ID de presentación:', presentationId);
    console.log('Título:', slideData.title);
    
    const presentation = SlidesApp.openById(presentationId);
    
    // Obtener layouts y usar uno específico
    const layouts = presentation.getLayouts();
    console.log('Layouts disponibles:', layouts.length);
    
    let targetLayout = null;
    
    // Buscar un layout con contenido
    for (let i = 0; i < layouts.length; i++) {
      const layoutName = layouts[i].getLayoutName();
      console.log(`Layout ${i}: ${layoutName}`);
      
      if (layoutName === 'TITLE_AND_BODY' || 
          layoutName === 'TITLE_AND_SUBTITLE' || 
          layoutName === 'MAIN_POINT') {
        targetLayout = layouts[i];
        console.log('Layout seleccionado:', layoutName);
        break;
      }
    }
    
    // Si no encontramos uno específico, usar el primero
    if (!targetLayout && layouts.length > 0) {
      targetLayout = layouts[0];
      console.log('Usando primer layout:', targetLayout.getLayoutName());
    }
    
    // Crear diapositiva con layout
    let slide;
    if (targetLayout) {
      slide = presentation.appendSlide(targetLayout);
      console.log('Diapositiva creada con layout');
    } else {
      slide = presentation.appendSlide();
      console.log('Diapositiva creada sin layout');
    }
    
    const shapes = slide.getShapes();
    console.log('Shapes en nueva diapositiva:', shapes.length);
    
    // Configurar título
    if (shapes.length > 0) {
      shapes[0].getText().setText(slideData.title || 'Diapositiva');
      shapes[0].getText().getTextStyle().setFontSize(28).setBold(true);
      console.log('Título configurado');
    }
    
    // Configurar subtítulo
    if (shapes.length > 1) {
      shapes[1].getText().setText(slideData.subtitle || '');
      shapes[1].getText().getTextStyle().setFontSize(16);
      console.log('Subtítulo configurado');
    }
    
    // Configurar contenido
    if (slideData.content && shapes.length > 2) {
      let contentText = '';
      
      if (typeof slideData.content === 'object') {
        Object.entries(slideData.content).forEach(([key, value]) => {
          contentText += `${key}: ${value}\n`;
        });
      } else {
        contentText = String(slideData.content);
      }
      
      shapes[2].getText().setText(contentText);
      shapes[2].getText().getTextStyle().setFontSize(14);
      console.log('Contenido configurado');
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
