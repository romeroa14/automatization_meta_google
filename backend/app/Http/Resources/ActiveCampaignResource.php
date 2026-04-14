<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveCampaignResource extends JsonResource
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
            'meta_campaign_name' => $this->meta_campaign_name,
            'campaign_status' => $this->campaign_status,
            'amount_spent' => $this->amount_spent,
            'campaign_daily_budget' => $this->campaign_daily_budget,
            'campaign_total_budget' => $this->campaign_total_budget,
            'campaign_start_time' => $this->campaign_start_time,
            'campaign_stop_time' => $this->campaign_stop_time,
            'created_at' => $this->created_at,
        ];
    }
}
