<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\FacebookAccount;
use App\Models\AdvertisingPlan;
use App\Services\CampaignCreationFlowService;
use App\Services\ConversationStateService;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            Log::info('📱 Webhook de Telegram recibido', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
                'data' => $request->all()
            ]);

            $update = $request->all();
            
            if (isset($update['message'])) {
                return $this->handleMessage($update['message']);
            }
            
            if (isset($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query']);
            }

            Log::warning('⚠️ Tipo de update no reconocido', ['update' => $update]);
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('❌ Error en webhook de Telegram', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'] ?? null;
        $username = $message['from']['username'] ?? 'Usuario';

        Log::info('💬 Mensaje recibido', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'username' => $username,
            'text' => $text
        ]);

        // Comandos disponibles
        $commands = [
            '/start' => 'startCommand',
            '/help' => 'helpCommand',
            '/crear_campana' => 'createCampaignCommand',
            '/otro_ad' => 'createAnotherAdCommand',
            '/mis_cuentas' => 'myAccountsCommand',
            '/planes' => 'plansCommand',
            '/estado' => 'statusCommand',
            '/cancelar' => 'cancelCommand',
            '/progreso' => 'progressCommand'
        ];

        $command = explode(' ', $text)[0];
        
        if (isset($commands[$command])) {
            return $this->{$commands[$command]}($chatId, $message);
        }

        // Si no es un comando, verificar si hay una conversación activa
        $conversationState = new ConversationStateService();
        $isActive = $conversationState->isConversationActive($chatId);
        
        Log::info('🔍 Verificando conversación activa', [
            'chat_id' => $chatId,
            'text' => $text,
            'is_active' => $isActive
        ]);
        
        if ($isActive) {
            return $this->handleConversationStep($chatId, $text);
        }

        // Si no hay conversación activa, mostrar ayuda
        return $this->sendMessage($chatId, $this->getHelpMessage());
    }

    private function startCommand($chatId, $message)
    {
        $welcomeMessage = "🤖 *Bienvenido al Bot de AdMétricas*\n\n";
        $welcomeMessage .= "Soy tu asistente para crear campañas publicitarias de Meta de forma rápida y eficiente.\n\n";
        $welcomeMessage .= "📋 *Comandos disponibles:*\n";
        $welcomeMessage .= "/crear_campana - Crear una nueva campaña\n";
        $welcomeMessage .= "/otro_ad - Crear otro anuncio (mantiene cuenta publicitaria)\n";
        $welcomeMessage .= "/mis_cuentas - Ver cuentas de Facebook disponibles\n";
        $welcomeMessage .= "/planes - Ver planes publicitarios disponibles\n";
        $welcomeMessage .= "/estado - Ver estado del sistema\n";
        $welcomeMessage .= "/help - Mostrar esta ayuda\n\n";
        $welcomeMessage .= "🚀 ¡Empecemos a crear campañas!";

        return $this->sendMessage($chatId, $welcomeMessage);
    }

    private function helpCommand($chatId, $message)
    {
        return $this->sendMessage($chatId, $this->getHelpMessage());
    }

    private function createCampaignCommand($chatId, $message)
    {
        $conversationState = new ConversationStateService();
        $flowService = new CampaignCreationFlowService();
        
        // Iniciar nueva conversación
        $conversationState->clearConversationState($chatId);
        $conversationState->updateConversationStep($chatId, 'start');
        
        $startMessage = $flowService->getStepMessage('start');
        return $this->sendMessage($chatId, $startMessage);
    }

    private function createAnotherAdCommand($chatId, $message)
    {
        $conversationState = new ConversationStateService();
        $flowService = new CampaignCreationFlowService();
        
        // Verificar si hay una conversación previa con cuenta publicitaria
        $previousState = $conversationState->getConversationState($chatId);
        
        if (!$previousState || !isset($previousState['data']['ad_account'])) {
            return $this->sendMessage($chatId, 
                "❌ *No hay cuenta publicitaria seleccionada.*\n\n" .
                "💡 *Opciones:*\n" .
                "• Usa /crear_campana para flujo completo\n" .
                "• O selecciona una cuenta publicitaria primero"
            );
        }
        
        // Iniciar nueva conversación pero mantener cuenta publicitaria
        $conversationState->clearConversationState($chatId);
        $conversationState->updateConversationStep($chatId, 'fanpage');
        
        // Preservar la cuenta publicitaria seleccionada
        $conversationState->updateConversationData($chatId, 'ad_account', $previousState['data']['ad_account']);
        
        // Obtener nombre de la cuenta publicitaria de forma segura
        $adAccountName = $previousState['data']['ad_account_name'] ?? 'Cuenta seleccionada';
        $conversationState->updateConversationData($chatId, 'ad_account_name', $adAccountName);
        
        $message = "🔄 *Crear Otro Anuncio*\n\n";
        $message .= "✅ *Cuenta publicitaria:* " . $adAccountName . "\n\n";
        $message .= $flowService->getStepMessage('fanpage');
        
        return $this->sendMessage($chatId, $message);
    }

    private function myAccountsCommand($chatId, $message)
    {
        try {
            $accounts = FacebookAccount::where('is_active', true)->get();
            
            if ($accounts->isEmpty()) {
                return $this->sendMessage($chatId, "❌ No hay cuentas de Facebook activas disponibles.");
            }

            $message = "📱 *Cuentas de Facebook Disponibles:*\n\n";
            foreach ($accounts as $account) {
                $message .= "🔹 *{$account->account_name}*\n";
                $message .= "   App ID: `{$account->app_id}`\n";
                $message .= "   Estado: " . ($account->is_active ? "✅ Activa" : "❌ Inactiva") . "\n\n";
            }

            return $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Error obteniendo cuentas de Facebook', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "❌ Error al obtener las cuentas de Facebook.");
        }
    }

    private function plansCommand($chatId, $message)
    {
        try {
            $plans = AdvertisingPlan::where('is_active', true)->get();
            
            if ($plans->isEmpty()) {
                return $this->sendMessage($chatId, "❌ No hay planes publicitarios disponibles.");
            }

            $message = "📊 *Planes Publicitarios Disponibles:*\n\n";
            foreach ($plans as $plan) {
                $message .= "🔹 *{$plan->name}*\n";
                $message .= "   Presupuesto diario: \${$plan->daily_budget}\n";
                $message .= "   Duración: {$plan->duration_days} días\n";
                $message .= "   Precio: \${$plan->client_price}\n\n";
            }

            return $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Error obteniendo planes publicitarios', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "❌ Error al obtener los planes publicitarios.");
        }
    }

    private function statusCommand($chatId, $message)
    {
        try {
            $userCount = User::count();
            $activeAccounts = FacebookAccount::where('is_active', true)->count();
            $activePlans = AdvertisingPlan::where('is_active', true)->count();

            $statusMessage = "📊 *Estado del Sistema*\n\n";
            $statusMessage .= "👥 Usuarios: {$userCount}\n";
            $statusMessage .= "📱 Cuentas FB activas: {$activeAccounts}\n";
            $statusMessage .= "📋 Planes activos: {$activePlans}\n";
            $statusMessage .= "🕐 Última actualización: " . now()->format('d/m/Y H:i:s') . "\n\n";
            $statusMessage .= "✅ Sistema funcionando correctamente";

            return $this->sendMessage($chatId, $statusMessage);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estado del sistema', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "❌ Error al obtener el estado del sistema.");
        }
    }

    private function handleCallbackQuery(array $callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        $queryId = $callbackQuery['id'];

        Log::info('🔘 Callback query recibido', [
            'chat_id' => $chatId,
            'data' => $data,
            'query_id' => $queryId
        ]);

        // Aquí manejaremos las respuestas a botones inline
        // Por ahora, solo confirmamos la recepción
        $this->answerCallbackQuery($queryId, "Comando procesado: {$data}");
        
        return response()->json(['ok' => true]);
    }

    private function mapTemplateData(array $templateData, array $existingData): array
    {
        $mappedData = $existingData; // Mantener datos existentes (cuenta, fanpage)
        
        // Mapear campos de la plantilla
        if (isset($templateData['campaign_name'])) {
            $mappedData['campaign_name'] = $templateData['campaign_name'];
        }
        
        if (isset($templateData['campaign_objective'])) {
            $mappedData['campaign_objective'] = $templateData['campaign_objective'];
        }
        
        if (isset($templateData['budget_type'])) {
            $mappedData['budget_type'] = $templateData['budget_type'];
        }
        
        if (isset($templateData['daily_budget'])) {
            $mappedData['daily_budget'] = $templateData['daily_budget'];
        }
        
        if (isset($templateData['start_date']) && isset($templateData['end_date'])) {
            $mappedData['dates'] = [
                'start' => $templateData['start_date'],
                'end' => $templateData['end_date']
            ];
        }
        
        if (isset($templateData['geolocation'])) {
            $mappedData['geolocation'] = $templateData['geolocation'];
        }
        
        if (isset($templateData['age_min']) && isset($templateData['age_max'])) {
            $mappedData['audience_details'] = $templateData['age_min'] . '-' . $templateData['age_max'] . ' ' . ($templateData['gender'] ?? 'ambos');
        }
        
        if (isset($templateData['ad_placement'])) {
            $mappedData['ad_placement'] = $templateData['ad_placement'];
        }
        
        if (isset($templateData['ad_name'])) {
            $mappedData['ad_name'] = $templateData['ad_name'];
        }
        
        if (isset($templateData['creative_type'])) {
            $mappedData['creative_type'] = $templateData['creative_type'];
        }
        
        if (isset($templateData['ad_copy'])) {
            $mappedData['ad_copy'] = $templateData['ad_copy'];
        }
        
        return $mappedData;
    }

    private function sendMessage($chatId, $text, $parseMode = 'Markdown')
    {
        $botToken = config('services.telegram.bot_token');
        
        if (!$botToken) {
            Log::error('❌ Bot token de Telegram no configurado');
            return response()->json(['ok' => false, 'error' => 'Bot token not configured']);
        }

        try {
            // Limpiar el texto para evitar problemas con Markdown
            $cleanText = $this->cleanMarkdownText($text);
            
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $cleanText,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok']) {
                    Log::info('✅ Mensaje enviado exitosamente', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id']
                    ]);
                    return response()->json(['ok' => true]);
                } else {
                    Log::error('❌ Error enviando mensaje', [
                        'error' => $data['description'],
                        'response' => $data
                    ]);
                    return response()->json(['ok' => false, 'error' => $data['description']]);
                }
            } else {
                Log::error('❌ Error HTTP enviando mensaje', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['ok' => false, 'error' => 'HTTP error']);
            }

        } catch (\Exception $e) {
            Log::error('❌ Excepción enviando mensaje', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
    
    private function cleanMarkdownText($text)
    {
        // Escapar caracteres especiales de Markdown que pueden causar problemas
        $text = str_replace(['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'], 
                           ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'], 
                           $text);
        
        // Limitar la longitud del mensaje (máximo 4096 caracteres)
        if (strlen($text) > 4096) {
            $text = substr($text, 0, 4093) . '...';
        }
        
        return $text;
    }

    private function answerCallbackQuery($queryId, $text = null)
    {
        $botToken = config('services.telegram.bot_token');
        
        if (!$botToken) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                'callback_query_id' => $queryId,
                'text' => $text,
                'show_alert' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Error respondiendo callback query', ['error' => $e->getMessage()]);
        }
    }

    private function processCampaignData($chatId, $text)
    {
        // Método temporalmente deshabilitado para evitar errores de base de datos
        return $this->sendMessage($chatId, "🔧 *Funcionalidad en desarrollo*\n\nPor ahora, usa /crear_campana para ver las instrucciones.");
    }

    private function createCampaign($chatId, $campaignData)
    {
        try {
            Log::info('🚀 Creando campaña', [
                'chat_id' => $chatId,
                'campaign_data' => $campaignData
            ]);

            // Aquí implementaremos la creación real de la campaña con Meta API
            // Por ahora, solo confirmamos que los datos están correctos
            
            $successMessage = "✅ *Campaña procesada exitosamente!*\n\n";
            $successMessage .= "📊 *Datos confirmados:*\n";
            $successMessage .= "• Nombre: " . $campaignData['name'] . "\n";
            $successMessage .= "• Objetivo: " . $campaignData['objective'] . "\n";
            $successMessage .= "• Presupuesto: $" . $campaignData['daily_budget'] . "/día\n";
            $successMessage .= "• Duración: " . $campaignData['duration_days'] . " días\n";
            $successMessage .= "• Cuenta: " . $campaignData['facebook_account'] . "\n\n";
            
            if ($campaignData['start_date'] && $campaignData['end_date']) {
                $successMessage .= "• Fechas: " . $campaignData['start_date'] . " - " . $campaignData['end_date'] . "\n\n";
            }
            
            $successMessage .= "🔄 *Próximos pasos:*\n";
            $successMessage .= "1. Validar cuenta de Facebook\n";
            $successMessage .= "2. Crear campaña en Meta\n";
            $successMessage .= "3. Configurar audiencia\n";
            $successMessage .= "4. Crear anuncios\n\n";
            $successMessage .= "⏳ *Procesando...*";

            return $this->sendMessage($chatId, $successMessage);

        } catch (\Exception $e) {
            Log::error('❌ Error creando campaña', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'campaign_data' => $campaignData
            ]);

            $errorMessage = "❌ *Error creando la campaña:*\n\n";
            $errorMessage .= "Error: " . $e->getMessage() . "\n\n";
            $errorMessage .= "💡 *Contacta al administrador para más ayuda.*";
            
            return $this->sendMessage($chatId, $errorMessage);
        }
    }

    private function handleConversationStep($chatId, $text)
    {
        try {
            $conversationState = new ConversationStateService();
            $flowService = new CampaignCreationFlowService();
            
            $state = $conversationState->getConversationState($chatId);
            $currentStep = $state['step'];
            
            Log::info('🔄 Procesando paso de conversación', [
                'chat_id' => $chatId,
                'current_step' => $currentStep,
                'input' => $text
            ]);
            
            // Manejar comandos especiales
            if (strtoupper($text) === 'CANCELAR') {
                $conversationState->clearConversationState($chatId);
                return $this->sendMessage($chatId, "❌ *Conversación cancelada.*\n\nUsa /crear_campana para comenzar de nuevo.");
            }
            
            // Manejar paginación de fanpages
            if ($currentStep === 'fanpage') {
                if (strtoupper($text) === 'SIGUIENTE' || strtoupper($text) === 'MÁS') {
                    $currentPage = $state['data']['fanpage_page'] ?? 1;
                    $nextPage = $currentPage + 1;
                    
                    // Guardar página actual
                    $conversationState->updateConversationData($chatId, 'fanpage_page', $nextPage);
                    
                    // Mostrar siguiente página
                    $message = $flowService->getFanpageMessagePaginated($nextPage);
                    return $this->sendMessage($chatId, $message);
                }
                
                if (strtoupper($text) === 'ANTERIOR') {
                    $currentPage = $state['data']['fanpage_page'] ?? 1;
                    $prevPage = max(1, $currentPage - 1);
                    
                    // Guardar página actual
                    $conversationState->updateConversationData($chatId, 'fanpage_page', $prevPage);
                    
                    // Mostrar página anterior
                    $message = $flowService->getFanpageMessagePaginated($prevPage);
                    return $this->sendMessage($chatId, $message);
                }
            }
            
            // Validar y procesar datos del paso actual
            $validation = $flowService->validateStepData($currentStep, $text);
            
            Log::info('🔍 Validación de paso', [
                'chat_id' => $chatId,
                'current_step' => $currentStep,
                'input' => $text,
                'validation_result' => $validation
            ]);
            
            if (!$validation['valid']) {
                $errorMessage = "❌ *Error de validación:*\n\n";
                $errorMessage .= $validation['error'] . "\n\n";
                $errorMessage .= "💡 *Intenta nuevamente o escribe 'CANCELAR' para salir.*";
                return $this->sendMessage($chatId, $errorMessage);
            }
            
            // Guardar datos del paso actual
            $conversationState->updateConversationData($chatId, $currentStep, $validation['data']);
            
            // Manejar selección de fanpage - obtener información de Instagram
            if ($currentStep === 'fanpage') {
                $selectedPageId = $validation['data'];
                $instagramInfo = $flowService->getInstagramInfoForPage($selectedPageId);
                
                if ($instagramInfo) {
                    // Guardar información de Instagram
                    $conversationState->updateConversationData($chatId, 'instagram_info', $instagramInfo);
                    
                    // Enviar mensaje con información de Instagram
                    $instagramMessage = "📸 *Información de Instagram encontrada:*\n\n";
                    $instagramMessage .= "👤 *Usuario:* @{$instagramInfo['username']}\n";
                    $instagramMessage .= "📝 *Nombre:* {$instagramInfo['name']}\n";
                    $instagramMessage .= "👥 *Seguidores:* " . number_format($instagramInfo['followers_count']) . "\n";
                    $instagramMessage .= "📊 *Seguidos:* " . number_format($instagramInfo['follows_count']) . "\n";
                    $instagramMessage .= "📸 *Publicaciones:* " . number_format($instagramInfo['media_count']) . "\n\n";
                    $instagramMessage .= "✅ *Instagram conectado exitosamente!*\n\n";
                    
                    $this->sendMessage($chatId, $instagramMessage);
                } else {
                    // Enviar mensaje de que no hay Instagram
                    $this->sendMessage($chatId, "📸 *Instagram:* No conectado a esta fanpage.\n\n✅ *Continuando con la configuración...*");
                }
            }
            
            // Manejar flujo de plantilla
            if ($currentStep === 'template_choice' && $validation['data'] === 'plantilla') {
                // Saltar al paso de plantilla
                $conversationState->updateConversationStep($chatId, 'template_form');
                $templateMessage = $flowService->getStepMessage('template_form');
                return $this->sendMessage($chatId, $templateMessage);
            }
            
            // Manejar procesamiento de plantilla
            if ($currentStep === 'template_form') {
                // Procesar todos los datos de la plantilla
                $templateData = $validation['data'];
                
                // Mapear datos de la plantilla a formato del sistema
                $mappedData = $this->mapTemplateData($templateData, $state['data']);
                
                // Guardar todos los datos mapeados
                foreach ($mappedData as $key => $value) {
                    $conversationState->updateConversationData($chatId, $key, $value);
                }
                
                // Saltar directamente a la revisión
                $conversationState->updateConversationStep($chatId, 'review');
                $reviewMessage = $flowService->getStepMessage('review', $mappedData);
                return $this->sendMessage($chatId, $reviewMessage);
            }
            
            // Obtener siguiente paso
            $nextStep = $flowService->getNextStep($currentStep);
            
            Log::info('🔄 Avanzando al siguiente paso', [
                'chat_id' => $chatId,
                'current_step' => $currentStep,
                'next_step' => $nextStep
            ]);
            
            if ($nextStep === 'complete') {
                // Crear campaña
                return $this->createCampaignFromConversation($chatId);
            }
            
            // Avanzar al siguiente paso
            $conversationState->updateConversationStep($chatId, $nextStep);
            $nextMessage = $flowService->getStepMessage($nextStep, $state['data']);
            
            Log::info('📤 Enviando mensaje del siguiente paso', [
                'chat_id' => $chatId,
                'next_step' => $nextStep,
                'message_length' => strlen($nextMessage)
            ]);
            
            return $this->sendMessage($chatId, $nextMessage);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en conversación', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'text' => $text
            ]);
            
            return $this->sendMessage($chatId, "❌ *Error procesando la conversación.*\n\nUsa /cancelar para salir o /crear_campana para comenzar de nuevo.");
        }
    }
    
    private function cancelCommand($chatId, $message)
    {
        $conversationState = new ConversationStateService();
        $conversationState->clearConversationState($chatId);
        
        return $this->sendMessage($chatId, "❌ *Conversación cancelada.*\n\nUsa /crear_campana para comenzar de nuevo.");
    }
    
    private function progressCommand($chatId, $message)
    {
        $conversationState = new ConversationStateService();
        
        if (!$conversationState->isConversationActive($chatId)) {
            return $this->sendMessage($chatId, "ℹ️ *No hay conversación activa.*\n\nUsa /crear_campana para comenzar.");
        }
        
        $summary = $conversationState->getConversationSummary($chatId);
        return $this->sendMessage($chatId, $summary);
    }
    
    private function createCampaignFromConversation($chatId)
    {
        try {
            $conversationState = new ConversationStateService();
            $state = $conversationState->getConversationState($chatId);
            
            Log::info('🚀 Creando campaña desde conversación', [
                'chat_id' => $chatId,
                'data' => $state['data']
            ]);
            
            // Convertir datos de la conversación al formato requerido por MetaCampaignCreatorService
            $campaignData = $this->convertConversationDataToCampaignData($state['data']);
            
            // Obtener cuenta de Facebook activa
            $facebookAccount = \App\Models\FacebookAccount::where('is_active', true)->first();
            
            if (!$facebookAccount) {
                return $this->sendMessage($chatId, "❌ *Error:* No hay cuenta de Facebook activa configurada.");
            }
            
            // Crear campaña usando el servicio
            $campaignCreator = new \App\Services\MetaCampaignCreatorService($facebookAccount);
            $result = $campaignCreator->createCampaign($campaignData);
            
            if ($result['success']) {
                $successMessage = "✅ *¡Campaña creada exitosamente!*\n\n";
                $successMessage .= "📊 *Detalles de la campaña:*\n";
                $successMessage .= "• Campaña ID: `{$result['campaign']['id']}`\n";
                $successMessage .= "• Conjunto de Anuncios ID: `{$result['adset']['id']}`\n";
                $successMessage .= "• Anuncio ID: `{$result['ad']['id']}`\n";
                $successMessage .= "• Nombre: {$campaignData['name']}\n";
                $successMessage .= "• Objetivo: {$campaignData['objective']}\n";
                $successMessage .= "• Presupuesto Diario: \${$campaignData['daily_budget']}\n";
                
                if (!empty($result['warnings'])) {
                    $successMessage .= "\n⚠️ *Advertencias:*\n";
                    foreach ($result['warnings'] as $warning) {
                        $successMessage .= "• {$warning}\n";
                    }
                }
                
                if ($result['is_development_mode']) {
                    $successMessage .= "\n💡 *Nota:* App en modo desarrollo. El anuncio se creó como placeholder.";
                    $successMessage .= "\n📝 *Para crear anuncios reales, necesitas hacer la app pública.*";
                }
                
                $successMessage .= "\n🎉 *Tu campaña está siendo procesada y estará activa en unos minutos.*";
                
            } else {
                $errorMessage = "❌ *Error creando la campaña:*\n\n";
                if (isset($result['error'])) {
                    $errorMessage .= "• {$result['error']}\n";
                }
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $errorMessage .= "• {$error}\n";
                    }
                }
                $errorMessage .= "\n💡 *Usa /crear_campana para intentar nuevamente.*";
                
                return $this->sendMessage($chatId, $errorMessage);
            }
            
            // Limpiar estado de conversación
            $conversationState->clearConversationState($chatId);
            
            return $this->sendMessage($chatId, $successMessage);
            
        } catch (\Exception $e) {
            Log::error('❌ Error creando campaña', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_id' => $chatId
            ]);
            
            return $this->sendMessage($chatId, "❌ *Error creando la campaña.*\n\nUsa /crear_campana para intentar nuevamente.");
        }
    }

    /**
     * Convertir datos de la conversación al formato requerido por MetaCampaignCreatorService
     */
    private function convertConversationDataToCampaignData(array $conversationData): array
    {
        // Obtener servicios necesarios
        $flowService = new \App\Services\CampaignCreationFlowService();
        $metaService = new \App\Services\MetaApiService();
        
        // Obtener cuenta de Facebook activa
        $facebookAccount = \App\Models\FacebookAccount::where('is_active', true)->first();
        
        // Obtener cuentas publicitarias y fanpages
        $adAccounts = $flowService->getAvailableFacebookAccounts();
        $fanpages = $flowService->getAvailableFanpages();
        
        // Mapear IDs a datos reales
        $selectedAdAccount = $adAccounts[$conversationData['ad_account'] - 1] ?? null;
        $selectedFanpage = $fanpages[$conversationData['fanpage'] - 1] ?? null;
        
        if (!$selectedAdAccount || !$selectedFanpage) {
            throw new \Exception('Cuenta publicitaria o fanpage no encontrada');
        }
        
        // Convertir objetivo de conversación a objetivo de Meta
        $objectiveMapping = [
            'TRAFFIC' => 'OUTCOME_TRAFFIC',
            'CONVERSIONS' => 'OUTCOME_SALES',
            'MESSAGES' => 'OUTCOME_ENGAGEMENT',
            'REACH' => 'OUTCOME_AWARENESS'
        ];
        
        $objective = $objectiveMapping[$conversationData['campaign_objective']] ?? 'OUTCOME_TRAFFIC';
        
        // Parsear audiencia
        $audienceDetails = $conversationData['audience_details'] ?? '18-65 Ambos';
        preg_match('/(\d+)-(\d+)/', $audienceDetails, $ageMatches);
        $ageMin = $ageMatches[1] ?? 18;
        $ageMax = $ageMatches[2] ?? 65;
        
        $genders = [1, 2]; // Ambos géneros por defecto
        if (strpos($audienceDetails, 'Hombres') !== false) {
            $genders = [2];
        } elseif (strpos($audienceDetails, 'Mujeres') !== false) {
            $genders = [1];
        }
        
        return [
            'name' => $conversationData['campaign_name'],
            'objective' => $objective,
            'ad_account_id' => $selectedAdAccount['app_id'],
            'page_id' => $selectedFanpage['page_id'],
            'daily_budget' => (int) $conversationData['daily_budget'],
            'geolocation' => $conversationData['geolocation'],
            'age_min' => (int) $ageMin,
            'age_max' => (int) $ageMax,
            'genders' => $genders,
            'ad_copy' => $conversationData['ad_copy'],
            'ad_name' => $conversationData['ad_name'],
            'link' => 'https://example.com', // Por defecto, se puede personalizar
            'description' => $conversationData['ad_copy'],
            'special_ad_categories' => []
        ];
    }

    private function getHelpMessage()
    {
        return "🤖 *Bot de AdMétricas - Ayuda*\n\n" .
               "📋 *Comandos disponibles:*\n" .
               "/start - Iniciar el bot\n" .
               "/crear_campana - Crear nueva campaña (flujo completo)\n" .
               "/otro_ad - Crear otro anuncio (mantiene cuenta publicitaria)\n" .
               "/mis_cuentas - Ver cuentas disponibles\n" .
               "/planes - Ver planes publicitarios\n" .
               "/estado - Estado del sistema\n" .
               "/progreso - Ver progreso de conversación activa\n" .
               "/cancelar - Cancelar conversación activa\n" .
               "/help - Mostrar esta ayuda\n\n" .
               "💡 *Tips:*\n" .
               "• Usa /crear_campana para comenzar el flujo completo\n" .
               "• Usa /otro_ad para crear anuncios adicionales sin repetir configuración";
    }
}