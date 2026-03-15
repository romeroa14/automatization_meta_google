<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsAppPhoneNumberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'phone_number' => $this->phone_number,
            'formatted_phone_number' => $this->getFormattedPhoneNumber(),
            'display_name' => $this->display_name,
            'phone_number_id' => $this->phone_number_id,
            'waba_id' => $this->waba_id,
            'webhook_url' => $this->webhook_url,
            'status' => $this->status,
            'quality_rating' => $this->quality_rating,
            'capabilities' => $this->capabilities,
            'settings' => $this->settings,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'is_default' => $this->is_default,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Relationships
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'leads_count' => $this->whenCounted('leads'),
            'conversations_count' => $this->whenCounted('conversations'),
        ];
    }
}
