<?php

namespace App\Services;

use App\Models\FacebookAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CampaignCreationFlowService
{
    protected array $steps = [
        'start' => 'Iniciar creación de campaña',
        'ad_account' => 'Seleccionar cuenta publicitaria',
        'fanpage' => 'Seleccionar fanpage',
        'template_choice' => 'Elegir método de creación',
        'template_form' => 'Formulario de plantilla',
        'campaign_name' => 'Nombre de la campaña',
        'campaign_objective' => 'Objetivo de la campaña',
        'budget_type' => 'Tipo de presupuesto',
        'daily_budget' => 'Presupuesto diario',
        'dates' => 'Fechas de la campaña',
        'geolocation' => 'Geolocalización',
        'conversion_location' => 'Ubicación de conversión',
        'audience_type' => 'Tipo de audiencia',
        'audience_details' => 'Detalles de la audiencia',
        'ad_placement' => 'Ubicación de anuncios',
        'ad_name' => 'Nombre del anuncio',
        'creative_type' => 'Tipo de creativo',
        'creative_content' => 'Contenido del creativo',
        'ad_copy' => 'Copy del anuncio',
        'conversation_template' => 'Plantilla de conversación',
        'review' => 'Revisar y confirmar',
        'create' => 'Crear campaña'
    ];

    protected array $campaignObjectives = [
        'TRAFFIC' => 'Tráfico al sitio web',
        'CONVERSION' => 'Conversiones',
        'REACH' => 'Alcance',
        'BRAND_AWARENESS' => 'Conciencia de marca',
        'ENGAGEMENT' => 'Compromiso',
        'LEAD_GENERATION' => 'Generación de leads',
        'SALES' => 'Ventas',
        'MESSAGES' => 'Mensajes',
        'APP_INSTALLS' => 'Instalaciones de app',
        'VIDEO_VIEWS' => 'Visualizaciones de video'
    ];

    protected array $budgetTypes = [
        'campaign' => 'Nivel Campaña',
        'adset' => 'Nivel Conjunto de Anuncios'
    ];

    protected array $audienceTypes = [
        'no_interests' => 'Sin intereses (Audiencia amplia)',
        'custom' => 'Audiencia personalizada',
        'lookalike' => 'Audiencia similar',
        'saved' => 'Audiencia guardada'
    ];

    protected array $adPlacements = [
        'automatic' => 'Advantage+ (Automático)',
        'manual' => 'Ubicación manual'
    ];

    protected array $creativeTypes = [
        'new_image' => 'Subir nueva imagen',
        'new_video' => 'Subir nuevo video',
        'existing_post' => 'Publicación existente de Instagram',
        'existing_facebook' => 'Publicación existente de Facebook'
    ];

    protected array $conversationTemplates = [
        'welcome' => 'Mensaje de bienvenida',
        'product_info' => 'Información del producto',
        'support' => 'Soporte al cliente',
        'custom' => 'Mensaje personalizado'
    ];

    public function getStepMessage(string $step, array $data = []): string
    {
        switch ($step) {
            case 'start':
                return $this->getStartMessage();
            case 'ad_account':
                return $this->getAdAccountMessage();
            case 'fanpage':
                return $this->getFanpageMessage($data);
            case 'template_choice':
                return $this->getTemplateChoiceMessage();
            case 'template_form':
                return $this->getTemplateFormMessage();
            case 'campaign_name':
                return $this->getCampaignNameMessage();
            case 'campaign_objective':
                return $this->getCampaignObjectiveMessage();
            case 'budget_type':
                return $this->getBudgetTypeMessage();
            case 'daily_budget':
                return $this->getDailyBudgetMessage();
            case 'dates':
                return $this->getDatesMessage();
            case 'geolocation':
                return $this->getGeolocationMessage();
            case 'conversion_location':
                return $this->getConversionLocationMessage();
            case 'audience_type':
                return $this->getAudienceTypeMessage();
            case 'audience_details':
                return $this->getAudienceDetailsMessage($data);
            case 'ad_placement':
                return $this->getAdPlacementMessage();
            case 'ad_name':
                return $this->getAdNameMessage();
            case 'creative_type':
                return $this->getCreativeTypeMessage();
            case 'creative_content':
                return $this->getCreativeContentMessage($data);
            case 'ad_copy':
                return $this->getAdCopyMessage();
            case 'conversation_template':
                return $this->getConversationTemplateMessage();
            case 'review':
                return $this->getReviewMessage($data);
            default:
                return "❌ Paso no reconocido: {$step}";
        }
    }

    private function getStartMessage(): string
    {
        $message = "🎯 *Crear Nueva Campaña - Paso 1*\n\n";
        $message .= "Vamos a crear una campaña publicitaria paso a paso.\n\n";
        $message .= "📋 *Información que necesitaremos:*\n";
        $message .= "• Cuenta publicitaria\n";
        $message .= "• Fanpage de destino\n";
        $message .= "• Nombre de la campaña\n";
        $message .= "• Objetivo de la campaña\n";
        $message .= "• Tipo de presupuesto\n";
        $message .= "• Presupuesto diario\n";
        $message .= "• Fechas de la campaña\n";
        $message .= "• Geolocalización\n";
        $message .= "• Configuración de audiencia\n";
        $message .= "• Ubicación de anuncios\n";
        $message .= "• Creativos y copy\n\n";
        $message .= "🚀 *¿Estás listo para comenzar?*\n";
        $message .= "Escribe 'SÍ' para continuar o 'CANCELAR' para salir.";

        return $message;
    }

    private function getAdAccountMessage(): string
    {
        $accounts = $this->getAvailableFacebookAccounts();
        
        $message = "💰 *Paso 2: Seleccionar Cuenta Publicitaria*\n\n";
        $message .= "Selecciona la cuenta publicitaria donde se creará la campaña:\n\n";
        
        if (empty($accounts)) {
            return $message . "❌ No hay cuentas publicitarias activas disponibles.";
        }
        
        foreach ($accounts as $index => $account) {
            $number = $index + 1;
            $message .= "{$number}. *{$account['account_name']}*\n";
            $message .= "   ID: `{$account['app_id']}`\n";
            $message .= "   Moneda: {$account['currency']}\n";
            $message .= "   Estado: {$account['status']}\n\n";
        }
        
        $message .= "💡 *Escribe el número de la cuenta que deseas usar.*";

        return $message;
    }

    private function getFanpageMessage(array $data): string
    {
        // Usar paginación por defecto (página 1)
        return $this->getFanpageMessagePaginated(1);
    }

    private function getTemplateChoiceMessage(): string
    {
        $message = "⚡ *Paso 4: Método de Creación*\n\n";
        $message .= "¿Cómo quieres crear tu campaña?\n\n";
        $message .= "🔄 *Opción 1: Paso a Paso*\n";
        $message .= "• Te guiaré pregunta por pregunta\n";
        $message .= "• Ideal para principiantes\n";
        $message .= "• Control total sobre cada detalle\n\n";
        $message .= "📋 *Opción 2: Plantilla Rápida*\n";
        $message .= "• Completa todos los datos de una vez\n";
        $message .= "• Ideal para usuarios avanzados\n";
        $message .= "• Creación más rápida\n\n";
        $message .= "💡 *Escribe 'paso' para ir paso a paso o 'plantilla' para usar plantilla.*";

        return $message;
    }

    private function getTemplateFormMessage(): string
    {
        $message = "📋 *Plantilla de Creación Rápida*\n\n";
        $message .= "Copia y pega esta plantilla, luego reemplaza los valores entre corchetes:\n\n";
        $message .= "```\n";
        $message .= "NOMBRE_CAMPANA: [Mi Campaña 2025]\n";
        $message .= "OBJETIVO: [CONVERSION|TRAFFIC|REACH|ENGAGEMENT|SALES|LEAD_GENERATION]\n";
        $message .= "TIPO_PRESUPUESTO: [campaign|adset]\n";
        $message .= "PRESUPUESTO_DIARIO: [100]\n";
        $message .= "FECHA_INICIO: [18/09/2025]\n";
        $message .= "FECHA_FIN: [25/09/2025]\n";
        $message .= "PAIS: [Venezuela]\n";
        $message .= "CIUDAD: [Caracas]\n";
        $message .= "UBICACION_CONVERSION: [SITIO_WEB|APP|MESSENGER|WHATSAPP|FACEBOOK]\n";
        $message .= "EDAD_MIN: [18]\n";
        $message .= "EDAD_MAX: [65]\n";
        $message .= "GENERO: [ambos|hombres|mujeres]\n";
        $message .= "UBICACION_ANUNCIOS: [automatic|facebook|instagram|messenger|audience_network]\n";
        $message .= "NOMBRE_ANUNCIO: [Mi Anuncio]\n";
        $message .= "TIPO_CREATIVO: [existing_post|new_post|carousel|video]\n";
        $message .= "COPY_ANUNCIO: [Mi mensaje publicitario]\n";
        $message .= "```\n\n";
        $message .= "📝 *Ejemplo completo:*\n";
        $message .= "```\n";
        $message .= "NOMBRE_CAMPANA: Campaña Verano 2025\n";
        $message .= "OBJETIVO: CONVERSION\n";
        $message .= "TIPO_PRESUPUESTO: adset\n";
        $message .= "PRESUPUESTO_DIARIO: 50\n";
        $message .= "FECHA_INICIO: 20/09/2025\n";
        $message .= "FECHA_FIN: 30/09/2025\n";
        $message .= "PAIS: Venezuela\n";
        $message .= "CIUDAD: Caracas\n";
        $message .= "UBICACION_CONVERSION: SITIO_WEB\n";
        $message .= "EDAD_MIN: 25\n";
        $message .= "EDAD_MAX: 55\n";
        $message .= "GENERO: ambos\n";
        $message .= "UBICACION_ANUNCIOS: automatic\n";
        $message .= "NOMBRE_ANUNCIO: Promoción Verano\n";
        $message .= "TIPO_CREATIVO: existing_post\n";
        $message .= "COPY_ANUNCIO: ¡Oferta especial de verano! No te la pierdas\n";
        $message .= "```\n\n";
        $message .= "💡 *Copia la plantilla, completa los datos y pégalos aquí.*";

        return $message;
    }

    private function parseTemplate(string $input): ?array
    {
        $lines = explode("\n", trim($input));
        $data = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, ':') === false) {
                continue;
            }
            
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (empty($key) || empty($value)) {
                continue;
            }
            
            // Mapear campos de la plantilla a campos del sistema
            switch ($key) {
                case 'NOMBRE_CAMPANA':
                    $data['campaign_name'] = $value;
                    break;
                case 'OBJETIVO':
                    $data['campaign_objective'] = $value;
                    break;
                case 'TIPO_PRESUPUESTO':
                    $data['budget_type'] = $value;
                    break;
                case 'PRESUPUESTO_DIARIO':
                    $data['daily_budget'] = floatval($value);
                    break;
                case 'FECHA_INICIO':
                    $data['start_date'] = $value;
                    break;
                case 'FECHA_FIN':
                    $data['end_date'] = $value;
                    break;
                case 'PAIS':
                    $data['country'] = $value;
                    break;
                case 'CIUDAD':
                    $data['city'] = $value;
                    break;
                case 'EDAD_MIN':
                    $data['age_min'] = intval($value);
                    break;
                case 'EDAD_MAX':
                    $data['age_max'] = intval($value);
                    break;
                case 'GENERO':
                    $data['gender'] = $value;
                    break;
                case 'UBICACION_ANUNCIOS':
                    $data['ad_placement'] = $value;
                    break;
                case 'NOMBRE_ANUNCIO':
                    $data['ad_name'] = $value;
                    break;
                case 'TIPO_CREATIVO':
                    $data['creative_type'] = $value;
                    break;
                case 'COPY_ANUNCIO':
                    $data['ad_copy'] = $value;
                    break;
                case 'UBICACION_CONVERSION':
                    $data['conversion_location'] = $value;
                    break;
            }
        }
        
        // Construir geolocalización
        if (isset($data['country']) && isset($data['city'])) {
            $data['geolocation'] = $data['city'] . ', ' . $data['country'];
        } elseif (isset($data['country'])) {
            $data['geolocation'] = $data['country'];
        } elseif (isset($data['city'])) {
            $data['geolocation'] = $data['city'];
        }
        
        // Validar campos requeridos
        $required = ['campaign_name', 'campaign_objective', 'budget_type', 'daily_budget', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return null;
            }
        }
        
        return $data;
    }

    private function getCampaignNameMessage(): string
    {
        $message = "🏷️ *Paso 4: Nombre de la Campaña*\n\n";
        $message .= "Escribe el nombre que tendrá tu campaña:\n\n";
        $message .= "📝 *Ejemplos:*\n";
        $message .= "• Campaña Verano 2025\n";
        $message .= "• Promoción Producto X\n";
        $message .= "• Black Friday 2025\n\n";
        $message .= "💡 *Escribe el nombre de tu campaña.*";

        return $message;
    }

    private function getCampaignObjectiveMessage(): string
    {
        $message = "🎯 *Paso 5: Objetivo de la Campaña*\n\n";
        $message .= "Selecciona el objetivo principal de tu campaña:\n\n";
        
        foreach ($this->campaignObjectives as $key => $description) {
            $message .= "• *{$key}*: {$description}\n";
        }
        
        $message .= "\n💡 *Escribe el código del objetivo (ej: CONVERSIONS).*";

        return $message;
    }

    private function getBudgetTypeMessage(): string
    {
        $message = "💰 *Paso 6: Tipo de Presupuesto*\n\n";
        $message .= "Selecciona dónde se configurará el presupuesto:\n\n";
        
        foreach ($this->budgetTypes as $key => $description) {
            $message .= "• *{$key}*: {$description}\n";
        }
        
        $message .= "\n📊 *Explicación:*\n";
        $message .= "• *Campaña*: El presupuesto se distribuye entre todos los conjuntos de anuncios\n";
        $message .= "• *Conjunto*: Cada conjunto de anuncios tiene su propio presupuesto\n\n";
        $message .= "💡 *Escribe 'campaign' o 'adset'.*";

        return $message;
    }

    private function getDailyBudgetMessage(): string
    {
        $message = "💵 *Paso 7: Presupuesto Diario*\n\n";
        $message .= "Escribe el presupuesto diario en USD:\n\n";
        $message .= "📊 *Ejemplos:*\n";
        $message .= "• 1 (para $1 USD por día)\n";
        $message .= "• 5 (para $5 USD por día)\n";
        $message .= "• 25.50 (para $25.50 USD por día)\n";
        $message .= "• 100 (para $100 USD por día)\n\n";
        $message .= "💡 *Puedes usar cualquier monto que desees.*\n\n";
        $message .= "💡 *Escribe el monto del presupuesto diario.*";

        return $message;
    }

    private function getDatesMessage(): string
    {
        $message = "📅 *Paso 8: Fechas de la Campaña*\n\n";
        $message .= "Especifica las fechas de inicio y fin de tu campaña:\n\n";
        $message .= "📝 *Formato:*\n";
        $message .= "• Fecha inicio: DD/MM/YYYY\n";
        $message .= "• Fecha fin: DD/MM/YYYY\n\n";
        $message .= "📊 *Ejemplos:*\n";
        $message .= "• Inicio: 20/09/2025\n";
        $message .= "• Fin: 30/09/2025\n\n";
        $message .= "💡 *Escribe las fechas en el formato indicado.*";

        return $message;
    }

    private function getGeolocationMessage(): string
    {
        $message = "🌍 *Paso 9: Geolocalización*\n\n";
        $message .= "Especifica las ubicaciones geográficas para tu campaña.\n\n";
        $message .= "📝 *Formato requerido para Facebook:*\n";
        $message .= "• **País:** VE (Venezuela), US (Estados Unidos), ES (España)\n";
        $message .= "• **Ciudad:** Caracas,VE o Madrid,ES\n";
        $message .= "• **Región:** Miranda,VE o California,US\n\n";
        $message .= "💡 *Ejemplos válidos:*\n";
        $message .= "• VE (todo Venezuela)\n";
        $message .= "• Caracas,VE (solo Caracas)\n";
        $message .= "• Miranda,VE (estado Miranda)\n";
        $message .= "• VE;CO (Venezuela y Colombia)\n";
        $message .= "• Caracas,VE;Madrid,ES (múltiples ciudades)\n\n";
        $message .= "💡 *Escribe las ubicaciones en el formato correcto.*";

        return $message;
    }

    private function getConversionLocationMessage(): string
    {
        $message = "🎯 *Paso 10: Ubicación de Conversión*\n\n";
        $message .= "Especifica dónde quieres que ocurran las conversiones:\n\n";
        $message .= "📍 *Opciones disponibles:*\n";
        $message .= "• **SITIO_WEB** - En tu sitio web\n";
        $message .= "• **APP** - En tu aplicación móvil\n";
        $message .= "• **MESSENGER** - En Messenger\n";
        $message .= "• **WHATSAPP** - En WhatsApp\n";
        $message .= "• **FACEBOOK** - En Facebook/Instagram\n\n";
        $message .= "💡 *Ejemplos:*\n";
        $message .= "• SITIO_WEB (para conversiones en tu sitio)\n";
        $message .= "• APP (para conversiones en tu app)\n";
        $message .= "• MESSENGER (para conversaciones en Messenger)\n\n";
        $message .= "💡 *Escribe la ubicación de conversión deseada.*";

        return $message;
    }

    private function getAudienceTypeMessage(): string
    {
        $message = "👥 *Paso 10: Tipo de Audiencia*\n\n";
        $message .= "Selecciona el tipo de audiencia para tu campaña:\n\n";
        
        foreach ($this->audienceTypes as $key => $description) {
            $message .= "• *{$key}*: {$description}\n";
        }
        
        $message .= "\n📊 *Explicación:*\n";
        $message .= "• *Sin intereses*: Audiencia amplia, menos segmentada\n";
        $message .= "• *Personalizada*: Define intereses, demografía, etc.\n";
        $message .= "• *Similar*: Basada en audiencia existente\n";
        $message .= "• *Guardada*: Audiencia previamente creada\n\n";
        $message .= "💡 *Escribe el tipo de audiencia que deseas usar.*";

        return $message;
    }

    private function getAudienceDetailsMessage(array $data): string
    {
        $audienceType = $data['audience_type'] ?? 'custom';
        
        $message = "🎯 *Paso 11: Detalles de la Audiencia*\n\n";
        
        switch ($audienceType) {
            case 'no_interests':
                $message .= "Configuración para audiencia amplia:\n\n";
                $message .= "📝 *Especifica:*\n";
                $message .= "• Edad mínima: 18\n";
                $message .= "• Edad máxima: 65\n";
                $message .= "• Género: Todos\n\n";
                break;
                
            case 'custom':
                $message .= "Configuración para audiencia personalizada:\n\n";
                $message .= "📝 *Especifica:*\n";
                $message .= "• Edad: 25-45\n";
                $message .= "• Género: Mujeres\n";
                $message .= "• Intereses: Moda, Belleza, Lifestyle\n";
                $message .= "• Comportamientos: Compradores online\n\n";
                break;
                
            case 'lookalike':
                $message .= "Configuración para audiencia similar:\n\n";
                $message .= "📝 *Especifica:*\n";
                $message .= "• Audiencia fuente: ID de audiencia\n";
                $message .= "• Similitud: 1% (más similar) o 10% (más amplia)\n\n";
                break;
        }
        
        $message .= "💡 *Escribe los detalles de la audiencia.*";

        return $message;
    }

    private function getAdPlacementMessage(): string
    {
        $message = "📍 *Paso 12: Ubicación de Anuncios*\n\n";
        $message .= "Selecciona dónde aparecerán tus anuncios:\n\n";
        
        foreach ($this->adPlacements as $key => $description) {
            $message .= "• *{$key}*: {$description}\n";
        }
        
        $message .= "\n📊 *Explicación:*\n";
        $message .= "• *Advantage+*: Meta optimiza automáticamente\n";
        $message .= "• *Manual*: Tú eliges las ubicaciones específicas\n\n";
        $message .= "💡 *Escribe 'automatic' o 'manual'.*";

        return $message;
    }

    private function getAdNameMessage(): string
    {
        $message = "🏷️ *Paso 13: Nombre del Anuncio*\n\n";
        $message .= "Escribe el nombre que tendrá tu anuncio:\n\n";
        $message .= "📝 *Ejemplos:*\n";
        $message .= "• Anuncio Principal\n";
        $message .= "• Promoción Verano\n";
        $message .= "• Oferta Especial\n\n";
        $message .= "💡 *Escribe el nombre de tu anuncio.*";

        return $message;
    }

    private function getCreativeTypeMessage(): string
    {
        $message = "🎨 *Paso 14: Tipo de Creativo*\n\n";
        $message .= "Selecciona el tipo de creativo para tu anuncio:\n\n";
        
        foreach ($this->creativeTypes as $key => $description) {
            $message .= "• *{$key}*: {$description}\n";
        }
        
        $message .= "\n💡 *Escribe el tipo de creativo que deseas usar.*";

        return $message;
    }

    private function getCreativeContentMessage(array $data): string
    {
        $creativeType = $data['creative_type'] ?? 'new_image';
        
        $message = "📸 *Paso 15: Contenido del Creativo*\n\n";
        
        switch ($creativeType) {
            case 'new_image':
                $message .= "Sube una nueva imagen:\n\n";
                $message .= "📝 *Especificaciones:*\n";
                $message .= "• Formato: JPG, PNG\n";
                $message .= "• Tamaño: 1080x1080px (recomendado)\n";
                $message .= "• Peso: Máximo 30MB\n\n";
                $message .= "💡 *Sube la imagen o escribe 'SALTAR' para continuar.*";
                break;
                
            case 'new_video':
                $message .= "Sube un nuevo video:\n\n";
                $message .= "📝 *Especificaciones:*\n";
                $message .= "• Formato: MP4, MOV\n";
                $message .= "• Duración: 15 segundos - 2 minutos\n";
                $message .= "• Peso: Máximo 4GB\n\n";
                $message .= "💡 *Sube el video o escribe 'SALTAR' para continuar.*";
                break;
                
            case 'existing_post':
                $message .= "Selecciona una publicación existente de Instagram:\n\n";
                $message .= "📝 *Escribe:*\n";
                $message .= "• URL de la publicación de Instagram\n";
                $message .= "• O ID de la publicación\n\n";
                $message .= "💡 *Proporciona la URL o ID de la publicación.*";
                break;
                
            case 'existing_facebook':
                $message .= "Selecciona una publicación existente de Facebook:\n\n";
                $message .= "📝 *Escribe:*\n";
                $message .= "• URL de la publicación de Facebook\n";
                $message .= "• O ID de la publicación\n\n";
                $message .= "💡 *Proporciona la URL o ID de la publicación.*";
                break;
        }

        return $message;
    }

    private function getAdCopyMessage(): string
    {
        $message = "✍️ *Paso 16: Copy del Anuncio*\n\n";
        $message .= "Escribe el texto que aparecerá en tu anuncio:\n\n";
        $message .= "📝 *Ejemplos:*\n";
        $message .= "• ¡Oferta especial! 50% de descuento\n";
        $message .= "• Descubre nuestra nueva colección\n";
        $message .= "• Envío gratis en compras superiores a $50\n\n";
        $message .= "💡 *Escribe el copy de tu anuncio.*";

        return $message;
    }

    private function getConversationTemplateMessage(): string
    {
        $message = "💬 *Paso 17: Plantilla de Conversación*\n\n";
        $message .= "Si tu objetivo es generar mensajes, selecciona la plantilla:\n\n";
        
        foreach ($this->conversationTemplates as $key => $description) {
            $message .= "• *{$key}*: {$description}\n";
        }
        
        $message .= "\n💡 *Escribe el tipo de plantilla o 'SALTAR' si no aplica.*";

        return $message;
    }

    private function getReviewMessage(array $data): string
    {
        $message = "📋 *Revisión Final - Paso 18*\n\n";
        $message .= "Revisa todos los datos de tu campaña:\n\n";
        
        $message .= "💰 *Cuenta Publicitaria:* " . ($data['ad_account_name'] ?? 'No especificada') . "\n";
        $message .= "📱 *Fanpage:* " . ($data['fanpage_name'] ?? 'No especificada') . "\n";
        $message .= "🏷️ *Nombre Campaña:* " . ($data['campaign_name'] ?? 'No especificado') . "\n";
        $message .= "🎯 *Objetivo:* " . ($data['campaign_objective'] ?? 'No especificado') . "\n";
        $message .= "💰 *Tipo Presupuesto:* " . ($data['budget_type'] ?? 'No especificado') . "\n";
        $message .= "💵 *Presupuesto Diario:* $" . ($data['daily_budget'] ?? 'No especificado') . "\n";
        $message .= "📅 *Fechas:* " . ($data['start_date'] ?? 'No especificada') . " - " . ($data['end_date'] ?? 'No especificada') . "\n";
        $message .= "🌍 *Geolocalización:* " . ($data['geolocation'] ?? 'No especificada') . "\n";
        $message .= "🎯 *Ubicación Conversión:* " . ($data['conversion_location'] ?? 'No especificada') . "\n";
        $message .= "👥 *Audiencia:* " . ($data['audience_type'] ?? 'No especificada') . "\n";
        $message .= "📍 *Ubicación Anuncios:* " . ($data['ad_placement'] ?? 'No especificada') . "\n";
        $message .= "🏷️ *Nombre Anuncio:* " . ($data['ad_name'] ?? 'No especificado') . "\n";
        $message .= "🎨 *Tipo Creativo:* " . ($data['creative_type'] ?? 'No especificado') . "\n";
        $message .= "✍️ *Copy:* " . ($data['ad_copy'] ?? 'No especificado') . "\n\n";
        
        $message .= "✅ *¿Todo está correcto?*\n";
        $message .= "Escribe 'CONFIRMAR' para crear la campaña o 'EDITAR' para modificar algo.";

        return $message;
    }

    private function getAvailableAdAccounts(): array
    {
        // Aquí obtendríamos las cuentas publicitarias reales de Meta API
        // Por ahora retornamos datos de ejemplo
        return [
            [
                'id' => 'act_123456789',
                'name' => 'ADMETRICAS.COM - Cuenta Principal',
                'currency' => 'USD'
            ]
        ];
    }

    

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getNextStep(string $currentStep): string
    {
        $steps = array_keys($this->steps);
        $currentIndex = array_search($currentStep, $steps);
        
        if ($currentIndex !== false && $currentIndex < count($steps) - 1) {
            return $steps[$currentIndex + 1];
        }
        
        return 'complete';
    }

    public function validateStepData(string $step, string $input): array
    {
        $result = ['valid' => false, 'data' => null, 'error' => null];
        
        switch ($step) {
            case 'start':
                // Para el paso start, solo aceptamos "SÍ" o "CANCELAR"
                if (strtoupper($input) === 'SÍ' || strtoupper($input) === 'SI') {
                    $result['valid'] = true;
                    $result['data'] = 'confirmed';
                } else {
                    $result['error'] = 'Escribe "SÍ" para continuar o "CANCELAR" para salir';
                }
                break;
                
            case 'ad_account':
                // Validar selección de cuenta publicitaria
                $input = trim($input);
                if (is_numeric($input) && intval($input) >= 1) {
                    $result['valid'] = true;
                    $result['data'] = intval($input);
                } else {
                    $result['error'] = 'Selecciona un número válido de la lista';
                }
                break;
                
            case 'fanpage':
                // Validar selección de fanpage
                $input = trim($input);
                if (is_numeric($input) && intval($input) >= 1) {
                    $result['valid'] = true;
                    $result['data'] = intval($input);
                } else {
                    $result['error'] = 'Selecciona un número válido de la lista';
                }
                break;
                
            case 'template_choice':
                // Validar elección de método
                $input = strtolower(trim($input));
                if (in_array($input, ['paso', 'plantilla', 'template'])) {
                    $result['valid'] = true;
                    $result['data'] = $input;
                } else {
                    $result['error'] = 'Escribe "paso" para ir paso a paso o "plantilla" para usar plantilla';
                }
                break;
                
            case 'template_form':
                // Procesar plantilla
                $templateData = $this->parseTemplate($input);
                if ($templateData) {
                    $result['valid'] = true;
                    $result['data'] = $templateData;
                } else {
                    $result['error'] = 'Formato de plantilla inválido. Usa el formato correcto con todos los campos requeridos.';
                }
                break;
                
            case 'campaign_name':
                if (strlen(trim($input)) >= 3) {
                    $result['valid'] = true;
                    $result['data'] = trim($input);
                } else {
                    $result['error'] = 'El nombre debe tener al menos 3 caracteres';
                }
                break;
                
            case 'campaign_objective':
                if (array_key_exists(strtoupper($input), $this->campaignObjectives)) {
                    $result['valid'] = true;
                    $result['data'] = strtoupper($input);
                } else {
                    $result['error'] = 'Objetivo no válido. Usa uno de los códigos disponibles';
                }
                break;
                
            case 'budget_type':
                if (in_array($input, array_keys($this->budgetTypes))) {
                    $result['valid'] = true;
                    $result['data'] = $input;
                } else {
                    $result['error'] = 'Tipo de presupuesto no válido. Escribe "campaign" o "adset"';
                }
                break;
                
            case 'daily_budget':
                $budget = floatval($input);
                if ($budget > 0 && $budget <= 10000) {
                    $result['valid'] = true;
                    $result['data'] = $budget;
                } else {
                    $result['error'] = 'El presupuesto debe ser mayor a $0 y menor a $10,000 USD';
                }
                break;
                
            case 'dates':
                // Validar formato de fechas DD/MM/YYYY
                $dates = explode(' - ', $input);
                if (count($dates) === 2) {
                    $startDate = trim($dates[0]);
                    $endDate = trim($dates[1]);
                    
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $startDate) && 
                        preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $endDate)) {
                        $result['valid'] = true;
                        $result['data'] = ['start' => $startDate, 'end' => $endDate];
                    } else {
                        $result['error'] = 'Formato de fecha inválido. Usa DD/MM/YYYY';
                    }
                } else {
                    $result['error'] = 'Formato inválido. Usa: DD/MM/YYYY - DD/MM/YYYY';
                }
                break;
                
            case 'geolocation':
                // Validar formato de geolocalización para Facebook
                $input = trim($input);
                if (strlen($input) >= 2) {
                    // Validar formato básico: códigos de país (VE, US, ES) o ciudad,país (Caracas,VE)
                    if (preg_match('/^[A-Z]{2}$/', $input) || // Código de país (VE, US, ES)
                        preg_match('/^[A-Za-z\s]+,[A-Z]{2}$/', $input) || // Ciudad,País (Caracas,VE)
                        preg_match('/^[A-Z]{2};[A-Z]{2}$/', $input) || // Múltiples países (VE;CO)
                        preg_match('/^[A-Za-z\s]+,[A-Z]{2};[A-Za-z\s]+,[A-Z]{2}$/', $input)) { // Múltiples ciudades
                        $result['valid'] = true;
                        $result['data'] = $input;
                    } else {
                        $result['error'] = 'Formato de geolocalización inválido. Usa códigos de país (VE, US, ES) o ciudad,país (Caracas,VE)';
                    }
                } else {
                    $result['error'] = 'La geolocalización es requerida y debe tener al menos 2 caracteres';
                }
                break;
                
            case 'conversion_location':
                $input = trim(strtoupper($input));
                $validLocations = ['SITIO_WEB', 'APP', 'MESSENGER', 'WHATSAPP', 'FACEBOOK'];
                if (in_array($input, $validLocations)) {
                    $result['valid'] = true;
                    $result['data'] = $input;
                } else {
                    $result['error'] = 'Ubicación de conversión no válida. Usa: SITIO_WEB, APP, MESSENGER, WHATSAPP, o FACEBOOK';
                }
                break;
                
            case 'audience_type':
                if (in_array($input, array_keys($this->audienceTypes))) {
                    $result['valid'] = true;
                    $result['data'] = $input;
                } else {
                    $result['error'] = 'Tipo de audiencia no válido';
                }
                break;
                
            case 'ad_placement':
                if (in_array($input, array_keys($this->adPlacements))) {
                    $result['valid'] = true;
                    $result['data'] = $input;
                } else {
                    $result['error'] = 'Ubicación de anuncios no válida. Escribe "automatic" o "manual"';
                }
                break;
                
            case 'creative_type':
                if (in_array($input, array_keys($this->creativeTypes))) {
                    $result['valid'] = true;
                    $result['data'] = $input;
                } else {
                    $result['error'] = 'Tipo de creativo no válido';
                }
                break;
                
            case 'creative_content':
                if (strlen(trim($input)) >= 1) {
                    $result['valid'] = true;
                    $result['data'] = trim($input);
                } else {
                    $result['error'] = 'El contenido del creativo es requerido';
                }
                break;
                
            case 'ad_copy':
                if (strlen(trim($input)) >= 10) {
                    $result['valid'] = true;
                    $result['data'] = trim($input);
                } else {
                    $result['error'] = 'El copy del anuncio es requerido y debe tener al menos 10 caracteres';
                }
                break;
                
            case 'conversation_template':
                if (strlen(trim($input)) >= 1) {
                    $result['valid'] = true;
                    $result['data'] = trim($input);
                } else {
                    $result['error'] = 'La plantilla de conversación es requerida';
                }
                break;
                
            case 'review':
                if (strtoupper($input) === 'CONFIRMAR') {
                    $result['valid'] = true;
                    $result['data'] = 'confirmed';
                } else {
                    $result['error'] = 'Escribe "CONFIRMAR" para crear la campaña o "EDITAR" para modificar algo';
                }
                break;
                
            default:
                $result['valid'] = true;
                $result['data'] = $input;
        }
        
        return $result;
    }

    public function getAvailableFacebookAccounts(): array
    {
        // Obtener cuentas reales de la API de Meta
        $facebookAccount = \App\Models\FacebookAccount::where('is_active', true)->first();
        
        if (!$facebookAccount) {
            return [];
        }
        
        $metaService = new \App\Services\MetaApiService();
        $adAccounts = $metaService->getAdAccounts($facebookAccount);
        
        // Formatear para el flujo
        $formatted = [];
        foreach ($adAccounts as $index => $account) {
            $formatted[] = [
                'id' => $index + 1,
                'account_name' => $account['name'],
                'app_id' => $account['id'],
                'access_token' => $facebookAccount->access_token,
                'currency' => $account['currency'],
                'status' => $account['status']
            ];
        }
        
        return $formatted;
    }

    public function getAvailableFanpages(?int $adAccountId = null): array
    {
        // Obtener fanpages reales de la API de Meta
        $facebookAccount = \App\Models\FacebookAccount::where('is_active', true)->first();
        
        if (!$facebookAccount) {
            return [];
        }
        
        $metaService = new \App\Services\MetaApiService();
        $pages = $metaService->getPages($facebookAccount);
        
        // Formatear para el flujo
        $formatted = [];
        foreach ($pages as $index => $page) {
            $formatted[] = [
                'id' => $index + 1,
                'page_name' => $page['name'],
                'page_id' => $page['id'],
                'category' => $page['category'],
                'access_token' => $page['access_token']
            ];
        }
        
        return $formatted;
    }

    /**
     * Obtener fanpages con paginación
     */
    public function getFanpagesPaginated(int $page = 1, int $perPage = 20): array
    {
        $fanpages = $this->getAvailableFanpages();
        $total = count($fanpages);
        $offset = ($page - 1) * $perPage;
        
        return [
            'data' => array_slice($fanpages, $offset, $perPage),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
            'has_next' => $page < ceil($total / $perPage),
            'has_prev' => $page > 1
        ];
    }

    /**
     * Generar mensaje de fanpages con paginación
     */
    public function getFanpageMessagePaginated(int $page = 1): string
    {
        $pagination = $this->getFanpagesPaginated($page);
        $fanpages = $pagination['data'];
        
        $message = "📱 *Paso 3: Seleccionar Fanpage*\n\n";
        $message .= "Selecciona la fanpage donde se publicará la campaña:\n\n";
        
        if (empty($fanpages)) {
            return $message . "❌ No hay fanpages disponibles.";
        }
        
        foreach ($fanpages as $index => $page) {
            $number = ($pagination['current_page'] - 1) * $pagination['per_page'] + $index + 1;
            $message .= "{$number}. *{$page['page_name']}*\n";
            $message .= "   ID: `{$page['page_id']}`\n";
            $message .= "   Categoría: {$page['category']}\n";
            
            // Verificar si tiene cuenta de Instagram conectada
            if (isset($page['instagram_account'])) {
                $message .= "   📸 Instagram: @{$page['instagram_account']['username']}\n";
                $message .= "   📊 Seguidores: " . number_format($page['instagram_account']['followers_count']) . "\n";
            } else {
                $message .= "   📸 Instagram: No conectado\n";
            }
            $message .= "\n";
        }
        
        // Información de paginación
        $message .= "📄 *Página {$pagination['current_page']} de {$pagination['total_pages']}*\n";
        $message .= "📊 *Mostrando " . count($fanpages) . " de {$pagination['total']} fanpages*\n\n";
        
        // Navegación
        if ($pagination['has_prev']) {
            $message .= "⬅️ *Escribe 'ANTERIOR' para ver la página anterior*\n";
        }
        if ($pagination['has_next']) {
            $message .= "➡️ *Escribe 'SIGUIENTE' para ver la página siguiente*\n";
        }
        
        $message .= "💡 *O escribe el número de la fanpage que deseas usar.*";

        return $message;
    }
}
