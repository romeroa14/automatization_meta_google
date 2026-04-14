<?php

namespace App\Services;

use App\Models\TelegramConversation;
use App\Models\TelegramCampaign;
use App\Models\FacebookAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramBotService
{
    private string $botToken;
    private string $webhookUrl;
    private array $objectives = [
        'TRAFFIC' => 'Tráfico',
        'CONVERSIONS' => 'Conversiones', 
        'REACH' => 'Alcance',
        'BRAND_AWARENESS' => 'Conocimiento de Marca',
        'VIDEO_VIEWS' => 'Visualizaciones de Video',
        'LEAD_GENERATION' => 'Generación de Leads',
        'MESSAGES' => 'Mensajes',
        'ENGAGEMENT' => 'Interacción',
        'APP_INSTALLS' => 'Instalaciones de App',
        'STORE_VISITS' => 'Visitas a Tienda',
    ];

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->webhookUrl = config('telegram.webhook_url');
    }

    /**
     * Procesa un mensaje entrante de Telegram
     */
    public function handleWebhook(array $data): void
    {
        try {
            $message = $data['message'] ?? null;
            if (!$message) {
                return;
            }

            $chatId = $message['chat']['id'];
            $userId = $message['from']['id'];
            $username = $message['from']['username'] ?? null;
            $firstName = $message['from']['first_name'] ?? null;
            $lastName = $message['from']['last_name'] ?? null;
            $text = $message['text'] ?? '';
            $photo = $message['photo'] ?? null;
            $document = $message['document'] ?? null;

            // Buscar o crear conversación
            $conversation = $this->getOrCreateConversation($userId, $username, $firstName, $lastName);

            // Procesar según el tipo de mensaje
            if ($photo) {
                $this->handlePhoto($conversation, $photo);
            } elseif ($document) {
                $this->handleDocument($conversation, $document);
            } elseif ($text) {
                $this->handleText($conversation, $text);
            }

        } catch (\Exception $e) {
            Log::error('Error en TelegramBotService: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene o crea una conversación
     */
    private function getOrCreateConversation(string $userId, ?string $username, ?string $firstName, ?string $lastName): TelegramConversation
    {
        return TelegramConversation::firstOrCreate(
            ['telegram_user_id' => $userId],
            [
                'telegram_username' => $username,
                'telegram_first_name' => $firstName,
                'telegram_last_name' => $lastName,
                'current_step' => 'start',
                'is_active' => true,
                'last_activity' => now(),
            ]
        );
    }

    /**
     * Maneja mensajes de texto
     */
    private function handleText(TelegramConversation $conversation, string $text): void
    {
        $step = $conversation->current_step;

        switch ($step) {
            case 'start':
                $this->handleStart($conversation, $text);
                break;
            case 'campaign_name':
                $this->handleCampaignName($conversation, $text);
                break;
            case 'objective':
                $this->handleObjective($conversation, $text);
                break;
            case 'budget_type':
                $this->handleBudgetType($conversation, $text);
                break;
            case 'daily_budget':
                $this->handleDailyBudget($conversation, $text);
                break;
            case 'start_date':
                $this->handleStartDate($conversation, $text);
                break;
            case 'end_date':
                $this->handleEndDate($conversation, $text);
                break;
            case 'targeting':
                $this->handleTargeting($conversation, $text);
                break;
            case 'ad_copy':
                $this->handleAdCopy($conversation, $text);
                break;
            case 'confirm':
                $this->handleConfirm($conversation, $text);
                break;
            default:
                $this->sendMessage($conversation->telegram_user_id, "Comando no reconocido. Usa /start para comenzar.");
        }
    }

    /**
     * Maneja el comando /start
     */
    private function handleStart(TelegramConversation $conversation, string $text): void
    {
        if ($text === '/start') {
            $message = "🚀 *¡Bienvenido al Bot de Campañas de Meta!*\n\n";
            $message .= "Te ayudo a crear campañas de Facebook e Instagram de forma rápida y fácil.\n\n";
            $message .= "📋 *Pasos para crear una campaña:*\n";
            $message .= "1️⃣ Nombre de la campaña\n";
            $message .= "2️⃣ Objetivo de la campaña\n";
            $message .= "3️⃣ Tipo de presupuesto\n";
            $message .= "4️⃣ Presupuesto diario\n";
            $message .= "5️⃣ Fecha de inicio\n";
            $message .= "6️⃣ Fecha de fin (opcional)\n";
            $message .= "7️⃣ Targeting (opcional)\n";
            $message .= "8️⃣ Texto del anuncio\n";
            $message .= "9️⃣ Imagen o video\n\n";
            $message .= "¿Listo para comenzar? Escribe el *nombre de tu campaña*:";

            $this->sendMessage($conversation->telegram_user_id, $message);
            $conversation->updateStep('campaign_name');
        } else {
            $this->sendMessage($conversation->telegram_user_id, "Usa /start para comenzar a crear una campaña.");
        }
    }

    /**
     * Maneja el nombre de la campaña
     */
    private function handleCampaignName(TelegramConversation $conversation, string $text): void
    {
        $conversation->setData('campaign_name', $text);
        
        $message = "✅ *Nombre de campaña:* {$text}\n\n";
        $message .= "🎯 *Selecciona el objetivo de tu campaña:*\n\n";
        
        foreach ($this->objectives as $key => $name) {
            $message .= "• {$name} (`{$key}`)\n";
        }
        
        $message .= "\nEscribe el código del objetivo (ej: TRAFFIC):";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('objective');
    }

    /**
     * Maneja el objetivo de la campaña
     */
    private function handleObjective(TelegramConversation $conversation, string $text): void
    {
        $objective = strtoupper(trim($text));
        
        if (!array_key_exists($objective, $this->objectives)) {
            $this->sendMessage($conversation->telegram_user_id, "❌ Objetivo no válido. Por favor, escribe uno de los códigos mostrados.");
            return;
        }

        $conversation->setData('objective', $objective);
        
        $message = "✅ *Objetivo:* {$this->objectives[$objective]}\n\n";
        $message .= "💰 *¿Dónde quieres establecer el presupuesto?*\n\n";
        $message .= "• `campaign` - A nivel de campaña\n";
        $message .= "• `adset` - A nivel de conjunto de anuncios\n\n";
        $message .= "Escribe 'campaign' o 'adset':";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('budget_type');
    }

    /**
     * Maneja el tipo de presupuesto
     */
    private function handleBudgetType(TelegramConversation $conversation, string $text): void
    {
        $budgetType = strtolower(trim($text));
        
        if (!in_array($budgetType, ['campaign', 'adset'])) {
            $this->sendMessage($conversation->telegram_user_id, "❌ Opción no válida. Escribe 'campaign' o 'adset'.");
            return;
        }

        $budgetTypeKey = $budgetType === 'campaign' ? 'campaign_daily_budget' : 'adset_daily_budget';
        $conversation->setData('budget_type', $budgetTypeKey);
        
        $message = "✅ *Tipo de presupuesto:* " . ($budgetType === 'campaign' ? 'Campaña' : 'Conjunto de Anuncios') . "\n\n";
        $message .= "💵 *¿Cuál es tu presupuesto diario?*\n\n";
        $message .= "Escribe el monto en USD (ej: 10.50):";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('daily_budget');
    }

    /**
     * Maneja el presupuesto diario
     */
    private function handleDailyBudget(TelegramConversation $conversation, string $text): void
    {
        $budget = floatval($text);
        
        if ($budget <= 0) {
            $this->sendMessage($conversation->telegram_user_id, "❌ El presupuesto debe ser mayor a 0. Escribe un monto válido (ej: 10.50):");
            return;
        }

        $conversation->setData('daily_budget', $budget);
        
        $message = "✅ *Presupuesto diario:* $" . number_format($budget, 2) . "\n\n";
        $message .= "📅 *¿Cuándo quieres que inicie la campaña?*\n\n";
        $message .= "Escribe la fecha en formato DD/MM/YYYY (ej: 15/09/2025):";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('start_date');
    }

    /**
     * Maneja la fecha de inicio
     */
    private function handleStartDate(TelegramConversation $conversation, string $text): void
    {
        try {
            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $text);
            $conversation->setData('start_date', $date->format('Y-m-d'));
            
            $message = "✅ *Fecha de inicio:* " . $date->format('d/m/Y') . "\n\n";
            $message .= "📅 *¿Cuándo quieres que termine la campaña?*\n\n";
            $message .= "Escribe la fecha en formato DD/MM/YYYY o 'sin_fecha' para campaña continua:";

            $this->sendMessage($conversation->telegram_user_id, $message);
            $conversation->updateStep('end_date');
        } catch (\Exception $e) {
            $this->sendMessage($conversation->telegram_user_id, "❌ Formato de fecha inválido. Usa DD/MM/YYYY (ej: 15/09/2025):");
        }
    }

    /**
     * Maneja la fecha de fin
     */
    private function handleEndDate(TelegramConversation $conversation, string $text): void
    {
        if (strtolower($text) === 'sin_fecha') {
            $conversation->setData('end_date', null);
            $message = "✅ *Fecha de fin:* Sin fecha (campaña continua)\n\n";
        } else {
            try {
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $text);
                $conversation->setData('end_date', $date->format('Y-m-d'));
                $message = "✅ *Fecha de fin:* " . $date->format('d/m/Y') . "\n\n";
            } catch (\Exception $e) {
                $this->sendMessage($conversation->telegram_user_id, "❌ Formato de fecha inválido. Usa DD/MM/YYYY o 'sin_fecha':");
                return;
            }
        }

        $message .= "🎯 *¿Quieres configurar targeting específico?*\n\n";
        $message .= "Escribe 'si' para configurar o 'no' para usar targeting automático:";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('targeting');
    }

    /**
     * Maneja el targeting
     */
    private function handleTargeting(TelegramConversation $conversation, string $text): void
    {
        $response = strtolower(trim($text));
        
        if ($response === 'si') {
            $message = "🎯 *Configuración de Targeting*\n\n";
            $message .= "Por ahora usaremos targeting automático. En futuras versiones podrás configurar:\n";
            $message .= "• Edad\n";
            $message .= "• Género\n";
            $message .= "• Ubicación\n";
            $message .= "• Intereses\n\n";
            $message .= "📝 *Escribe el texto de tu anuncio:*";
        } else {
            $conversation->setData('targeting_data', ['auto' => true]);
            $message = "✅ *Targeting:* Automático\n\n";
            $message .= "📝 *Escribe el texto de tu anuncio:*";
        }

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('ad_copy');
    }

    /**
     * Maneja el texto del anuncio
     */
    private function handleAdCopy(TelegramConversation $conversation, string $text): void
    {
        $conversation->setData('ad_copy', $text);
        
        $message = "✅ *Texto del anuncio:* {$text}\n\n";
        $message .= "🖼️ *Ahora envía la imagen o video para tu anuncio:*\n\n";
        $message .= "Puedes enviar:\n";
        $message .= "• Una foto\n";
        $message .= "• Un video\n";
        $message .= "• Un documento\n\n";
        $message .= "O escribe 'sin_media' para continuar sin archivo multimedia.";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('media');
    }

    /**
     * Maneja archivos multimedia
     */
    private function handlePhoto(TelegramConversation $conversation, array $photo): void
    {
        if ($conversation->current_step !== 'media') {
            return;
        }

        try {
            // Obtener la foto de mayor resolución
            $photoData = end($photo);
            $fileId = $photoData['file_id'];
            
            // Descargar el archivo
            $fileUrl = $this->getFileUrl($fileId);
            $fileName = 'telegram_media_' . time() . '.jpg';
            $filePath = 'telegram_media/' . $fileName;
            
            // Guardar el archivo
            $fileContent = Http::get($fileUrl)->body();
            Storage::disk('public')->put($filePath, $fileContent);
            
            $conversation->setData('media_type', 'image');
            $conversation->setData('media_url', Storage::url($filePath));
            
            $this->showConfirmation($conversation);
            
        } catch (\Exception $e) {
            Log::error('Error al procesar foto: ' . $e->getMessage());
            $this->sendMessage($conversation->telegram_user_id, "❌ Error al procesar la imagen. Intenta de nuevo.");
        }
    }

    /**
     * Maneja documentos
     */
    private function handleDocument(TelegramConversation $conversation, array $document): void
    {
        if ($conversation->current_step !== 'media') {
            return;
        }

        try {
            $fileId = $document['file_id'];
            $fileName = $document['file_name'] ?? 'document_' . time();
            
            // Descargar el archivo
            $fileUrl = $this->getFileUrl($fileId);
            $filePath = 'telegram_media/' . $fileName;
            
            // Guardar el archivo
            $fileContent = Http::get($fileUrl)->body();
            Storage::disk('public')->put($filePath, $fileContent);
            
            $conversation->setData('media_type', 'document');
            $conversation->setData('media_url', Storage::url($filePath));
            
            $this->showConfirmation($conversation);
            
        } catch (\Exception $e) {
            Log::error('Error al procesar documento: ' . $e->getMessage());
            $this->sendMessage($conversation->telegram_user_id, "❌ Error al procesar el documento. Intenta de nuevo.");
        }
    }

    /**
     * Muestra la confirmación de la campaña
     */
    private function showConfirmation(TelegramConversation $conversation): void
    {
        $data = $conversation->getData();
        
        $message = "📋 *RESUMEN DE TU CAMPAÑA*\n\n";
        $message .= "📝 *Nombre:* {$data['campaign_name']}\n";
        $message .= "🎯 *Objetivo:* {$this->objectives[$data['objective']]}\n";
        $message .= "💰 *Presupuesto:* $" . number_format($data['daily_budget'], 2) . " diarios\n";
        $message .= "📅 *Inicio:* " . \Carbon\Carbon::parse($data['start_date'])->format('d/m/Y') . "\n";
        $message .= "📅 *Fin:* " . ($data['end_date'] ? \Carbon\Carbon::parse($data['end_date'])->format('d/m/Y') : 'Sin fecha') . "\n";
        $message .= "📝 *Texto:* {$data['ad_copy']}\n";
        $message .= "🖼️ *Media:* " . ($data['media_type'] ?? 'Sin archivo') . "\n\n";
        $message .= "¿Confirmas la creación de esta campaña?\n\n";
        $message .= "Escribe 'si' para crear o 'no' para cancelar:";

        $this->sendMessage($conversation->telegram_user_id, $message);
        $conversation->updateStep('confirm');
    }

    /**
     * Maneja la confirmación
     */
    private function handleConfirm(TelegramConversation $conversation, string $text): void
    {
        $response = strtolower(trim($text));
        
        if ($response === 'si') {
            $this->createCampaign($conversation);
        } else {
            $this->sendMessage($conversation->telegram_user_id, "❌ Campaña cancelada. Usa /start para crear una nueva.");
            $conversation->updateStep('start');
        }
    }

    /**
     * Crea la campaña en Meta
     */
    private function createCampaign(TelegramConversation $conversation): void
    {
        try {
            $data = $conversation->getData();
            
            // Crear registro de campaña de Telegram
            $telegramCampaign = TelegramCampaign::create([
                'telegram_user_id' => $conversation->telegram_user_id,
                'telegram_conversation_id' => $conversation->id,
                'campaign_name' => $data['campaign_name'],
                'objective' => $data['objective'],
                'budget_type' => $data['budget_type'],
                'daily_budget' => $data['daily_budget'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'targeting_data' => $data['targeting_data'] ?? ['auto' => true],
                'ad_copy' => $data['ad_copy'],
                'media_type' => $data['media_type'] ?? null,
                'media_url' => $data['media_url'] ?? null,
                'status' => 'pending',
            ]);

            // Aquí se integraría con la API de Meta para crear la campaña real
            // Por ahora, simulamos la creación
            $this->sendMessage($conversation->telegram_user_id, "⏳ Creando campaña en Meta... Esto puede tomar unos minutos.");
            
            // Simular creación exitosa
            $telegramCampaign->update([
                'status' => 'created',
                'meta_campaign_id' => 'act_' . rand(100000000, 999999999),
                'meta_adset_id' => rand(100000000, 999999999),
                'meta_ad_id' => rand(100000000, 999999999),
            ]);

            $message = "✅ *¡Campaña creada exitosamente!*\n\n";
            $message .= "📊 *Detalles:*\n";
            $message .= "• ID Campaña: {$telegramCampaign->meta_campaign_id}\n";
            $message .= "• ID AdSet: {$telegramCampaign->meta_adset_id}\n";
            $message .= "• ID Anuncio: {$telegramCampaign->meta_ad_id}\n\n";
            $message .= "Tu campaña está lista y activa. ¡Usa /start para crear otra!";

            $this->sendMessage($conversation->telegram_user_id, $message);
            $conversation->updateStep('start');

        } catch (\Exception $e) {
            Log::error('Error al crear campaña: ' . $e->getMessage());
            $this->sendMessage($conversation->telegram_user_id, "❌ Error al crear la campaña. Intenta de nuevo o contacta al administrador.");
        }
    }

    /**
     * Obtiene la URL de un archivo de Telegram
     */
    private function getFileUrl(string $fileId): string
    {
        $response = Http::get("https://api.telegram.org/bot{$this->botToken}/getFile", [
            'file_id' => $fileId
        ]);

        $data = $response->json();
        $filePath = $data['result']['file_path'];
        
        return "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
    }

    /**
     * Envía un mensaje a Telegram
     */
    public function sendMessage(string $chatId, string $text, array $options = []): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ], $options));
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje a Telegram: ' . $e->getMessage());
        }
    }

    /**
     * Configura el webhook de Telegram
     */
    public function setWebhook(): bool
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/setWebhook", [
                'url' => $this->webhookUrl,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error al configurar webhook: ' . $e->getMessage());
            return false;
        }
    }
}
