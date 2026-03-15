<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendMessageToN8nJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Solo intentar una vez
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $leadId,
        public string $messageId,
        public array $processedData,
        public string $fromNumber,
        public string $profileName,
        public string $timestamp,
        public string $messageType,
        public string $originalInterventionTime // Timestamp de cuando se programó el job
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $lead = Lead::find($this->leadId);
            
            if (!$lead) {
                Log::warning('⚠️ Lead no encontrado para enviar a n8n', [
                    'lead_id' => $this->leadId,
                    'message_id' => $this->messageId,
                ]);
                return;
            }

            // Verificar si el empleado escribió después de programar este job
            // Si last_human_intervention_at es más reciente que originalInterventionTime,
            // significa que el empleado escribió, entonces NO enviar a n8n
            if ($lead->last_human_intervention_at) {
                $interventionTime = \Carbon\Carbon::parse($lead->last_human_intervention_at);
                $originalTime = \Carbon\Carbon::parse($this->originalInterventionTime);
                
                if ($interventionTime->gt($originalTime)) {
                    Log::info('⏸️ Job cancelado: Empleado escribió después de programar el envío a n8n', [
                        'lead_id' => $this->leadId,
                        'message_id' => $this->messageId,
                        'intervention_time' => $interventionTime->toDateTimeString(),
                        'original_time' => $originalTime->toDateTimeString(),
                    ]);
                    return; // No enviar a n8n, el empleado ya intervino
                }
            }

            // Verificar si han pasado 5 minutos desde la última intervención
            if (!$lead->shouldSendToN8n()) {
                $minutesSinceIntervention = now()->diffInMinutes($lead->last_human_intervention_at ?? now());
                Log::info('⏸️ Job cancelado: Aún no han pasado 5 minutos desde la intervención', [
                    'lead_id' => $this->leadId,
                    'message_id' => $this->messageId,
                    'minutes_since_intervention' => $minutesSinceIntervention,
                ]);
                return; // Aún no es momento de enviar
            }

            // Si han pasado 5 minutos y el empleado no escribió, enviar a n8n
            $n8nUrl = config('services.n8n.whatsapp_webhook_url');
            
            if (!$n8nUrl) {
                Log::warning('URL de n8n para WhatsApp no configurada');
                return;
            }

            $data = [
                'messageId' => $this->messageId,
                'messageText' => $this->processedData['messageText'],
                'fromNumber' => $this->fromNumber,
                'profileName' => $this->profileName,
                'timestamp' => $this->timestamp,
                'accessToken' => config('services.whatsapp.access_token'),
                'phoneNumberId' => config('services.whatsapp.phone_number_id'),
                'platform' => 'whatsapp',
                'messageType' => $this->messageType,
                'contentType' => $this->processedData['contentType'],
                'hasImage' => $this->processedData['hasImage'],
                'imageUrl' => $this->processedData['imageUrl'],
                'imageId' => $this->processedData['imageId'] ?? null,
                'imageMimeType' => $this->processedData['imageMimeType'] ?? null,
                'imageSha256' => $this->processedData['imageSha256'] ?? null,
                'videoId' => $this->processedData['videoId'] ?? null,
                'videoMimeType' => $this->processedData['videoMimeType'] ?? null,
                'audioId' => $this->processedData['audioId'] ?? null,
                'audioMimeType' => $this->processedData['audioMimeType'] ?? null,
                'documentId' => $this->processedData['documentId'] ?? null,
                'documentMimeType' => $this->processedData['documentMimeType'] ?? null,
                'documentFilename' => $this->processedData['documentFilename'] ?? null
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('✅ Mensaje enviado a n8n después de esperar 5 minutos', [
                    'lead_id' => $this->leadId,
                    'message_id' => $this->messageId,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('❌ Error enviando mensaje a n8n después de esperar', [
                    'lead_id' => $this->leadId,
                    'message_id' => $this->messageId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en SendMessageToN8nJob', [
                'lead_id' => $this->leadId,
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

