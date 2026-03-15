<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * Lógica simple:
     * - message_text: Mensaje del CLIENTE (burbuja blanca, izquierda)
     * - response: Respuesta del BOT (burbuja verde, derecha)
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Log para debug - verificar qué datos tiene cada conversación
        \Log::info('📤 ConversationResource', [
            'id' => $this->id,
            'has_message_text' => !empty($this->message_text),
            'has_response' => !empty($this->response),
            'message_text_preview' => substr($this->message_text ?? '', 0, 50),
            'response_preview' => substr($this->response ?? '', 0, 50),
        ]);
        
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'message_text' => $this->message_text,
            'response' => $this->response,
            'platform' => $this->platform,
            'timestamp' => $this->timestamp,
            'created_at' => $this->created_at,
        ];
    }
}
