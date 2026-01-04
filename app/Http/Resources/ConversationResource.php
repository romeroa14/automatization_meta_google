<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Obtener valores directamente del modelo
        $responseValue = $this->response;
        $messageTextValue = $this->message_text;
        
        $data = [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'message_text' => $messageTextValue,
            'response' => $responseValue, // Asegurar que response siempre se incluya (incluso si es null)
            'is_client_message' => $this->is_client_message,
            'is_employee' => $this->is_employee,
            'platform' => $this->platform,
            'timestamp' => $this->timestamp,
            'created_at' => $this->created_at,
        ];
        
        // Log para debug si es respuesta del bot
        if (!$this->is_client_message) {
            \Log::info('📤 ConversationResource - Bot response', [
                'id' => $this->id,
                'has_response' => !empty($responseValue),
                'has_message_text' => !empty($messageTextValue),
                'response_length' => strlen($responseValue ?? ''),
                'message_text_length' => strlen($messageTextValue ?? ''),
                'response_preview' => substr($responseValue ?? '', 0, 100),
                'response_value_raw' => $responseValue, // Valor crudo para verificar
                'data_response_field' => $data['response'], // Verificar que esté en el array
            ]);
        }
        
        return $data;
    }
}
