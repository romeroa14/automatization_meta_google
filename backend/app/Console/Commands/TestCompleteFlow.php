<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignCreationFlowService;
use App\Services\ConversationStateService;

class TestCompleteFlow extends Command
{
    protected $signature = 'telegram:test-complete-flow {chat_id}';
    protected $description = 'Prueba el flujo completo de conversación del bot de Telegram';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        
        $this->info("🧪 Probando flujo completo de conversación para chat ID: {$chatId}");
        
        $conversationState = new ConversationStateService();
        $flowService = new CampaignCreationFlowService();
        
        // Limpiar estado previo
        $conversationState->clearConversationState($chatId);
        
        // Simular flujo completo
        $steps = [
            ['step' => 'start', 'input' => 'SÍ'],
            ['step' => 'ad_account', 'input' => '1'],
            ['step' => 'fanpage', 'input' => '1'],
            ['step' => 'campaign_name', 'input' => 'Campaña Test Completa'],
            ['step' => 'campaign_objective', 'input' => 'CONVERSIONS'],
            ['step' => 'budget_type', 'input' => 'campaign'],
            ['step' => 'daily_budget', 'input' => '10'],
        ];
        
        foreach ($steps as $index => $stepData) {
            $this->info("\n🔄 Paso " . ($index + 1) . ": {$stepData['step']}");
            $this->info("Input: {$stepData['input']}");
            
            // Obtener estado actual
            $state = $conversationState->getConversationState($chatId);
            $currentStep = $state['step'];
            
            $this->info("Estado actual: {$currentStep}");
            
            // Validar input
            $validation = $flowService->validateStepData($currentStep, $stepData['input']);
            $this->info("Validación: " . ($validation['valid'] ? '✅ Válido' : '❌ Inválido'));
            
            if (!$validation['valid']) {
                $this->error("Error: " . $validation['error']);
                return Command::FAILURE;
            }
            
            // Guardar datos
            $conversationState->updateConversationData($chatId, $currentStep, $validation['data']);
            
            // Obtener siguiente paso
            $nextStep = $flowService->getNextStep($currentStep);
            $this->info("Siguiente paso: {$nextStep}");
            
            if ($nextStep === 'complete') {
                $this->info("🎉 ¡Flujo completado!");
                break;
            }
            
            // Avanzar al siguiente paso
            $conversationState->updateConversationStep($chatId, $nextStep);
            
            // Mostrar mensaje del siguiente paso
            $nextMessage = $flowService->getStepMessage($nextStep, $state['data']);
            $this->info("Mensaje del siguiente paso:");
            $this->line(substr($nextMessage, 0, 100) . "...");
        }
        
        // Mostrar estado final
        $finalState = $conversationState->getConversationState($chatId);
        $this->info("\n📊 Estado final:");
        $this->line(json_encode($finalState, JSON_PRETTY_PRINT));
        
        return Command::SUCCESS;
    }
}
