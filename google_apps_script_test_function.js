/**
 * Función de prueba que puedes ejecutar manualmente desde el editor
 */

function testManualExecution() {
  console.log('=== PRUEBA MANUAL ===');
  console.log('Timestamp:', new Date().toISOString());
  
  try {
    // Simular datos que recibimos de Laravel
    const testData = {
      action: 'create_slide',
      presentation_id: '1GCiRXs-dI90M38CVQP2ZYGXiAbHSmWVp1Y_Pl8-iM0M',
      slide_index: 1,
      slide_data: {
        type: 'campaign',
        title: 'Estivaneli | 18/08/2025 - 24/08/2025',
        subtitle: 'Campaña ID: 120232230932260153',
        content: {
          reach: '6,591',
          impressions: '9,679',
          clicks: '403',
          ctr: '4.16%',
          cpm: '$0.74',
          cpc: '$0.02',
          frequency: '1.47',
          total_interactions: '6,433',
          interaction_rate: '66.46%',
          video_views_p100: '0',
          video_completion_rate: '0.00%'
        },
        layout: 'image'
      }
    };
    
    console.log('Datos de prueba:', JSON.stringify(testData, null, 2));
    
    // Probar la función handleCreateSlide
    const result = handleCreateSlide(testData);
    
    console.log('Resultado:', result);
    console.log('=== PRUEBA COMPLETADA ===');
    
  } catch (error) {
    console.error('Error en prueba manual:', error);
    console.error('Stack trace:', error.stack);
  }
}

/**
 * Función para probar la creación de presentación
 */

function testCreatePresentation() {
  console.log('=== PRUEBA CREACIÓN DE PRESENTACIÓN ===');
  console.log('Timestamp:', new Date().toISOString());
  
  try {
    const testData = {
      action: 'create_presentation',
      title: 'PRUEBA MANUAL - ' + new Date().toISOString(),
      description: 'Prueba de creación manual'
    };
    
    console.log('Datos de prueba:', JSON.stringify(testData, null, 2));
    
    const result = handleCreatePresentation(testData);
    
    console.log('Resultado:', result);
    console.log('=== PRUEBA COMPLETADA ===');
    
  } catch (error) {
    console.error('Error en prueba manual:', error);
    console.error('Stack trace:', error.stack);
  }
}

/**
 * Función para verificar el layout de una diapositiva
 */

function testSlideLayout() {
  console.log('=== PRUEBA LAYOUT DE DIAPOSITIVA ===');
  
  try {
    // Crear una presentación de prueba
    const presentation = SlidesApp.create('Prueba Layout - ' + new Date().toISOString());
    console.log('Presentación creada:', presentation.getId());
    
    // Obtener la primera diapositiva
    const slides = presentation.getSlides();
    console.log('Número de slides:', slides.length);
    
    if (slides.length > 0) {
      const firstSlide = slides[0];
      const shapes = firstSlide.getShapes();
      console.log('Número de shapes en primera slide:', shapes.length);
      
      // Mostrar información de cada shape
      shapes.forEach((shape, index) => {
        console.log(`Shape ${index}:`);
        console.log('  - Tipo:', shape.getShapeType());
        console.log('  - Texto actual:', shape.getText().asString());
        console.log('  - Posición X:', shape.getLeft());
        console.log('  - Posición Y:', shape.getTop());
        console.log('  - Ancho:', shape.getWidth());
        console.log('  - Alto:', shape.getHeight());
      });
    }
    
    // Crear una nueva diapositiva
    const newSlide = presentation.appendSlide();
    const newShapes = newSlide.getShapes();
    console.log('Número de shapes en nueva slide:', newShapes.length);
    
    // Mostrar información de cada shape en la nueva diapositiva
    newShapes.forEach((shape, index) => {
      console.log(`Nueva Shape ${index}:`);
      console.log('  - Tipo:', shape.getShapeType());
      console.log('  - Texto actual:', shape.getText().asString());
      console.log('  - Posición X:', shape.getLeft());
      console.log('  - Posición Y:', shape.getTop());
      console.log('  - Ancho:', shape.getWidth());
      console.log('  - Alto:', shape.getHeight());
    });
    
    console.log('=== PRUEBA LAYOUT COMPLETADA ===');
    
  } catch (error) {
    console.error('Error en prueba layout:', error);
    console.error('Stack trace:', error.stack);
  }
}
