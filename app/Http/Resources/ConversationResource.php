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
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'message_text' => $this->message_text,
            'is_client_message' => $this->is_client_message,
            'response' => $this->response,
            'platform' => $this->platform,
            'timestamp' => $this->timestamp,
            'created_at' => $this->created_at,
        ];
    }
}
