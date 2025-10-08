<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppLeadService
{
    protected $n8nUrl;
    protected $airtableApiKey;
    protected $airtableBaseId;

    public function __construct()
    {
        $this->n8nUrl = config('services.n8n.whatsapp_webhook_url');
        $this->airtableApiKey = config('services.airtable.api_key');
        $this->airtableBaseId = config('services.airtable.base_id');
    }

    /**
     * Procesa un mensaje de WhatsApp y determina si es un lead de alto valor
     */
    public function processWhatsAppMessage(array $messageData): array
    {
        try {
            $messages = $messageData['messages'] ?? [];
            $contacts = $messageData['contacts'] ?? [];
            $results = [];

            foreach ($messages as $message) {
                $messageId = $message['id'] ?? '';
                $messageText = $message['text']['body'] ?? '';
                $fromNumber = $message['from'] ?? '';
                $timestamp = $message['timestamp'] ?? '';

                // Buscar nombre del contacto
                $profileName = '';
                foreach ($contacts as $contact) {
                    if ($contact['wa_id'] === $fromNumber) {
                        $profileName = $contact['profile']['name'] ?? '';
                        break;
                    }
                }

                // Determinar si es un lead de alto valor
                $isHighValueLead = $this->isHighValueLead($messageText, $profileName);

                $result = [
                    'messageId' => $messageId,
                    'messageText' => $messageText,
                    'fromNumber' => $fromNumber,
                    'profileName' => $profileName,
                    'timestamp' => $timestamp,
                    'platform' => 'whatsapp',
                    'isHighValueLead' => $isHighValueLead,
                    'leadScore' => $this->calculateLeadScore($messageText, $profileName),
                    'keywords' => $this->extractKeywords($messageText)
                ];

                // Si es un lead de alto valor, procesarlo
                if ($isHighValueLead) {
                    $this->processHighValueLead($result);
                }

                $results[] = $result;
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message for leads', [
                'error' => $e->getMessage(),
                'data' => $messageData
            ]);
            return [];
        }
    }

    /**
     * Determina si un mensaje representa un lead de alto valor
     */
    private function isHighValueLead(string $messageText, string $profileName): bool
    {
        $highValueKeywords = [
            // Palabras de intención de compra
            'comprar', 'comprar', 'adquirir', 'obtener', 'necesito', 'quiero',
            'precio', 'costo', 'valor', 'cuanto cuesta', 'presupuesto',
            
            // Palabras de urgencia
            'urgente', 'rápido', 'inmediato', 'ya', 'ahora', 'hoy',
            
            // Palabras de negocio
            'empresa', 'negocio', 'proyecto', 'inversión', 'oportunidad',
            'cliente', 'venta', 'marketing', 'publicidad', 'campaña',
            
            // Palabras de consulta específica
            'consulta', 'información', 'detalles', 'especificaciones',
            'requisitos', 'necesidades', 'objetivos'
        ];

        $messageLower = strtolower($messageText);
        $nameLower = strtolower($profileName);

        // Verificar longitud del mensaje (mensajes largos suelen ser más valiosos)
        $isLongMessage = strlen($messageText) > 50;

        // Verificar palabras clave
        $hasHighValueKeywords = false;
        foreach ($highValueKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                $hasHighValueKeywords = true;
                break;
            }
        }

        // Verificar si el nombre sugiere un negocio
        $isBusinessName = $this->isBusinessName($profileName);

        return $isLongMessage || $hasHighValueKeywords || $isBusinessName;
    }

    /**
     * Calcula un puntaje de lead basado en el contenido del mensaje
     */
    private function calculateLeadScore(string $messageText, string $profileName): int
    {
        $score = 0;
        $messageLower = strtolower($messageText);

        // Puntuación por longitud del mensaje
        $score += min(strlen($messageText) / 10, 10);

        // Puntuación por palabras clave de alto valor
        $highValueWords = ['comprar', 'precio', 'urgente', 'empresa', 'negocio', 'inversión'];
        foreach ($highValueWords as $word) {
            if (strpos($messageLower, $word) !== false) {
                $score += 5;
            }
        }

        // Puntuación por nombre de negocio
        if ($this->isBusinessName($profileName)) {
            $score += 15;
        }

        // Puntuación por uso de mayúsculas (puede indicar urgencia)
        $uppercaseRatio = strlen(preg_replace('/[^A-Z]/', '', $messageText)) / strlen($messageText);
        if ($uppercaseRatio > 0.3) {
            $score += 5;
        }

        return min($score, 100); // Máximo 100 puntos
    }

    /**
     * Extrae palabras clave relevantes del mensaje
     */
    private function extractKeywords(string $messageText): array
    {
        $keywords = [];
        $messageLower = strtolower($messageText);

        $keywordCategories = [
            'intention' => ['comprar', 'necesito', 'quiero', 'busco', 'requiero'],
            'urgency' => ['urgente', 'rápido', 'inmediato', 'ya', 'ahora'],
            'business' => ['empresa', 'negocio', 'proyecto', 'inversión'],
            'product' => ['producto', 'servicio', 'solución', 'herramienta'],
            'contact' => ['llamar', 'contactar', 'escribir', 'mensaje']
        ];

        foreach ($keywordCategories as $category => $words) {
            foreach ($words as $word) {
                if (strpos($messageLower, $word) !== false) {
                    $keywords[$category][] = $word;
                }
            }
        }

        return $keywords;
    }

    /**
     * Determina si un nombre sugiere ser de un negocio
     */
    private function isBusinessName(string $name): bool
    {
        $businessIndicators = [
            's.a.', 's.r.l.', 'c.a.', 'ltda', 'inc', 'corp', 'ltd',
            'empresa', 'negocio', 'consultora', 'agencia', 'studio',
            'group', 'solutions', 'services', 'consulting'
        ];

        $nameLower = strtolower($name);
        foreach ($businessIndicators as $indicator) {
            if (strpos($nameLower, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Procesa un lead de alto valor
     */
    private function processHighValueLead(array $leadData): void
    {
        try {
            Log::info('🎯 Procesando lead de alto valor de WhatsApp', $leadData);

            // Enviar a N8N para procesamiento
            $this->sendToN8n($leadData);

            // También guardar localmente si es necesario
            $this->saveLeadLocally($leadData);

        } catch (\Exception $e) {
            Log::error('Error procesando lead de alto valor', [
                'error' => $e->getMessage(),
                'leadData' => $leadData
            ]);
        }
    }

    /**
     * Envía datos del lead a N8N
     */
    private function sendToN8n(array $leadData): void
    {
        try {
            if (!$this->n8nUrl) {
                Log::warning('URL de n8n para WhatsApp no configurada');
                return;
            }

            $data = [
                'messageId' => $leadData['messageId'],
                'messageText' => $leadData['messageText'],
                'fromNumber' => $leadData['fromNumber'],
                'profileName' => $leadData['profileName'],
                'timestamp' => $leadData['timestamp'],
                'platform' => 'whatsapp',
                'isHighValueLead' => $leadData['isHighValueLead'],
                'leadScore' => $leadData['leadScore'],
                'keywords' => $leadData['keywords'],
                'accessToken' => config('services.whatsapp.access_token'),
                'phoneNumberId' => config('services.whatsapp.phone_number_id')
            ];

            $response = Http::post($this->n8nUrl, $data);

            if ($response->successful()) {
                Log::info('Lead de WhatsApp enviado a n8n exitosamente', [
                    'messageId' => $leadData['messageId'],
                    'leadScore' => $leadData['leadScore'],
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Error enviando lead de WhatsApp a n8n', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error enviando lead de WhatsApp a n8n', [
                'error' => $e->getMessage(),
                'messageId' => $leadData['messageId']
            ]);
        }
    }

    /**
     * Guardar lead localmente (opcional)
     */
    private function saveLeadLocally(array $leadData): void
    {
        // Aquí podrías guardar en una tabla de leads local
        // Por ahora solo logueamos
        Log::info('💾 Lead guardado localmente', [
            'messageId' => $leadData['messageId'],
            'leadScore' => $leadData['leadScore'],
            'keywords' => $leadData['keywords']
        ]);
    }

    /**
     * Busca conversaciones existentes en Airtable
     */
    public function searchExistingConversation(string $userId): ?array
    {
        try {
            $url = "https://api.airtable.com/v0/{$this->airtableBaseId}/Conversations";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->airtableApiKey}",
                'Content-Type' => 'application/json'
            ])->get($url, [
                'filterByFormula' => "{User ID} = '{$userId}'",
                'maxRecords' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['records'][0] ?? null;
            }

            Log::error('Error buscando conversación en Airtable', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error buscando conversación en Airtable', [
                'error' => $e->getMessage(),
                'userId' => $userId
            ]);
            return null;
        }
    }

    /**
     * Crea un nuevo lead en Airtable
     */
    public function createLeadInAirtable(array $leadData): ?array
    {
        try {
            $url = "https://api.airtable.com/v0/{$this->airtableBaseId}/Leads";
            
            $fields = [
                'User ID' => $leadData['fromNumber'],
                'Name' => $leadData['profileName'],
                'Platform' => 'whatsapp',
                'Message Text' => $leadData['messageText'],
                'Lead Score' => $leadData['leadScore'],
                'Is High Value Lead' => $leadData['isHighValueLead'],
                'Keywords' => json_encode($leadData['keywords']),
                'Timestamp' => $leadData['timestamp'],
                'Status' => 'new',
                'Source' => 'whatsapp_automation'
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->airtableApiKey}",
                'Content-Type' => 'application/json'
            ])->post($url, [
                'fields' => $fields
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Lead creado exitosamente en Airtable', [
                    'recordId' => $data['id'],
                    'leadScore' => $leadData['leadScore']
                ]);
                return $data;
            }

            Log::error('Error creando lead en Airtable', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creando lead en Airtable', [
                'error' => $e->getMessage(),
                'leadData' => $leadData
            ]);
            return null;
        }
    }
}
