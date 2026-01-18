<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number', // Renamed from phone
        'client_name', // Renamed from name
        'intent', // Renamed from intention
        'lead_level', // New
        'stage',
        'confidence_score', // Renamed from confidence
        'bot_disabled', // Si es true, el bot no responderá (intervención humana)
        'last_human_intervention_at', // Última vez que un agente humano escribió
    ];

    protected $casts = [
        'bot_disabled' => 'boolean',
        'confidence_score' => 'decimal:2',
        'last_human_intervention_at' => 'datetime',
    ];

    /**
     * Verificar si el bot puede responder (han pasado 20 minutos desde la última intervención humana)
     */
    public function canBotRespond(): bool
    {
        // Si bot_disabled es false, el bot puede responder
        if (!$this->bot_disabled) {
            return true;
        }

        // Si bot_disabled es true, verificar si han pasado 20 minutos
        if ($this->last_human_intervention_at) {
            $minutesSinceIntervention = now()->diffInMinutes($this->last_human_intervention_at);
            return $minutesSinceIntervention >= 20;
        }

        // Si no hay timestamp, permitir respuesta (caso edge)
        return true;
    }

    /**
     * Verificar si se debe enviar mensaje a n8n (han pasado 5 minutos desde la última intervención humana)
     * Si un empleado escribió recientemente, NO enviar a n8n para evitar que el bot responda
     */
    public function shouldSendToN8n(): bool
    {
        // Si bot_disabled es false, siempre enviar a n8n
        if (!$this->bot_disabled) {
            return true;
        }

        // Si bot_disabled es true, verificar si han pasado 5 minutos
        if ($this->last_human_intervention_at) {
            $minutesSinceIntervention = now()->diffInMinutes($this->last_human_intervention_at);
            return $minutesSinceIntervention >= 5; // Esperar 5 minutos antes de enviar a n8n
        }

        // Si no hay timestamp, permitir envío (caso edge)
        return true;
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
