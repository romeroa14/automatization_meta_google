<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'plan' => $this->plan,
            'n8n_webhook_url' => $this->n8n_webhook_url,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Relationships
            'phone_numbers_count' => $this->whenCounted('whatsappPhoneNumbers'),
            'phone_numbers' => WhatsAppPhoneNumberResource::collection($this->whenLoaded('whatsappPhoneNumbers')),
            'users_count' => $this->whenCounted('users'),
            
            // User role in this organization
            'user_role' => $this->when(
                $request->user(),
                fn() => $this->users()
                    ->where('user_id', $request->user()->id)
                    ->first()?->pivot->role
            ),
        ];
    }
}
