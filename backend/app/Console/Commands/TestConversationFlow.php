<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignCreationFlowService;
use App\Services\ConversationStateService;

class TestConversationFlow extends Command
{
    protected $signature = 'telegram:test-flow {chat_id}';
    protected $description = 'Prueba el flujo de conversación del bot de Telegram';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        
        $this->info("🧪 Probando flujo de conversación para chat ID: {$chatId}");
        
        // Limpiar estado previo
        $conversationState = new ConversationStateService();
        $conversationState->clearConversationState($chatId);
        
        // Iniciar conversación
        $conversationState->updateConversationStep($chatId, 'start');
        
        $this->info("✅ Estado inicial configurado");
        
        // Probar paso 1: SÍ
        $this->info("🔄 Probando paso 1: SÍ");
        $flowService = new CampaignCreationFlowService();
        $validation = $flowService->validateStepData('start', 'SÍ');
        
        $this->info("Validación resultado: " . json_encode($validation));
        
        if ($validation['valid']) {
            $conversationState->updateConversationData($chatId, 'start', $validation['data']);
            $nextStep = $flowService->getNextStep('start');
            $this->info("✅ Siguiente paso: {$nextStep}");
            
            $conversationState->updateConversationStep($chatId, $nextStep);
            $nextMessage = $flowService->getStepMessage($nextStep);
            
            $this->info("📤 Mensaje del siguiente paso:");
            $this->line($nextMessage);
        } else {
            $this->error("❌ Validación falló: " . $validation['error']);
        }
        
        // Mostrar estado final
        $state = $conversationState->getConversationState($chatId);
        $this->info("📊 Estado final: " . json_encode($state, JSON_PRETTY_PRINT));
        
        return Command::SUCCESS;
    }
}
