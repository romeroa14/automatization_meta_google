<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConversationStateService
{
    protected int $cacheTimeout = 3600; // 1 hora

    public function getConversationState(int $chatId): array
    {
        $key = "telegram_conversation_{$chatId}";
        $state = Cache::get($key, []);
        
        if (empty($state)) {
            $state = [
                'step' => 'start',
                'data' => [],
                'started_at' => now(),
                'last_activity' => now()
            ];
            $this->saveConversationState($chatId, $state);
        }
        
        return $state;
    }

    public function saveConversationState(int $chatId, array $state): void
    {
        $key = "telegram_conversation_{$chatId}";
        $state['last_activity'] = now();
        Cache::put($key, $state, $this->cacheTimeout);
        
        Log::info('💾 Estado de conversación guardado', [
            'chat_id' => $chatId,
            'step' => $state['step'],
            'data_keys' => array_keys($state['data'])
        ]);
    }

    public function updateConversationStep(int $chatId, string $step): void
    {
        $state = $this->getConversationState($chatId);
        $state['step'] = $step;
        $this->saveConversationState($chatId, $state);
    }

    public function updateConversationData(int $chatId, string $key, $value): void
    {
        $state = $this->getConversationState($chatId);
        $state['data'][$key] = $value;
        $this->saveConversationState($chatId, $state);
    }

    public function clearConversationState(int $chatId): void
    {
        $key = "telegram_conversation_{$chatId}";
        Cache::forget($key);
        
        Log::info('🗑️ Estado de conversación limpiado', ['chat_id' => $chatId]);
    }

    public function isConversationActive(int $chatId): bool
    {
        $state = $this->getConversationState($chatId);
        return !empty($state['step']) && $state['step'] !== 'start';
    }

    public function getConversationProgress(int $chatId): array
    {
        $state = $this->getConversationState($chatId);
        $flowService = new CampaignCreationFlowService();
        $steps = array_keys($flowService->getSteps());
        
        $currentStepIndex = array_search($state['step'], $steps);
        $totalSteps = count($steps);
        $progress = $currentStepIndex !== false ? (($currentStepIndex + 1) / $totalSteps) * 100 : 0;
        
        return [
            'current_step' => $state['step'],
            'current_step_index' => $currentStepIndex,
            'total_steps' => $totalSteps,
            'progress_percentage' => round($progress, 1),
            'data_collected' => count($state['data']),
            'started_at' => $state['started_at'],
            'last_activity' => $state['last_activity']
        ];
    }

    public function getConversationSummary(int $chatId): string
    {
        $state = $this->getConversationState($chatId);
        $progress = $this->getConversationProgress($chatId);
        
        $summary = "📊 *Resumen de la Conversación*\n\n";
        $summary .= "🔄 *Progreso:* {$progress['progress_percentage']}% completado\n";
        $summary .= "📍 *Paso actual:* {$progress['current_step']}\n";
        $summary .= "📝 *Datos recopilados:* {$progress['data_collected']} campos\n";
        $summary .= "⏰ *Iniciado:* " . $state['started_at']->format('d/m/Y H:i:s') . "\n";
        $summary .= "🕐 *Última actividad:* " . $state['last_activity']->format('d/m/Y H:i:s') . "\n\n";
        
        if (!empty($state['data'])) {
            $summary .= "📋 *Datos recopilados:*\n";
            foreach ($state['data'] as $key => $value) {
                $summary .= "• {$key}: {$value}\n";
            }
        }
        
        return $summary;
    }
}
