<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramConversation extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'current_step',
        'conversation_data',
        'is_active',
        'last_activity',
    ];

    protected $casts = [
        'conversation_data' => 'array',
        'is_active' => 'boolean',
        'last_activity' => 'datetime',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(TelegramCampaign::class, 'telegram_conversation_id');
    }

    public function getFullNameAttribute(): string
    {
        $name = $this->telegram_first_name ?? '';
        if ($this->telegram_last_name) {
            $name .= ' ' . $this->telegram_last_name;
        }
        return trim($name) ?: $this->telegram_username ?? 'Usuario';
    }

    public function updateStep(string $step, array $data = []): void
    {
        $conversationData = $this->conversation_data ?? [];
        $conversationData = array_merge($conversationData, $data);
        
        $this->update([
            'current_step' => $step,
            'conversation_data' => $conversationData,
            'last_activity' => now(),
        ]);
    }

    public function getData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->conversation_data ?? [];
        }
        
        return data_get($this->conversation_data, $key, $default);
    }

    public function setData(string $key, $value): void
    {
        $conversationData = $this->conversation_data ?? [];
        data_set($conversationData, $key, $value);
        
        $this->update([
            'conversation_data' => $conversationData,
            'last_activity' => now(),
        ]);
    }
}
