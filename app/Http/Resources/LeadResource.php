<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
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
            'client_name' => $this->client_name,
            'phone_number' => $this->phone_number,
            'intent' => $this->intent,
            'lead_level' => $this->lead_level,
            'stage' => $this->stage,
            'confidence_score' => $this->confidence_score,
            'created_at' => $this->created_at,
        ];
    }
}
