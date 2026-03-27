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
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'message_text' => $this->message_text,
            'response' => $this->response,
            'is_client_message' => (boolean) $this->is_client_message,
            'is_employee' => (boolean) $this->is_employee,
            'platform' => $this->platform,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'created_at' => $this->created_at,
        ];
    }
}
