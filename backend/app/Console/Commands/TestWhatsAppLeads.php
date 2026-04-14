<?php

namespace App\Console\Commands;

use App\Services\WhatsAppLeadService;
use Illuminate\Console\Command;

class TestWhatsAppLeads extends Command
{
    protected $signature = 'whatsapp:test-leads {--message=} {--name=} {--number=}';
    protected $description = 'Test WhatsApp lead detection with sample data';

    public function handle()
    {
        $message = $this->option('message') ?: 'Hola, necesito información sobre sus servicios de marketing digital. ¿Cuál es el precio?';
        $name = $this->option('name') ?: 'Empresa ABC S.A.';
        $number = $this->option('number') ?: '+584241234567';

        $this->info('🧪 Testing WhatsApp Lead Detection...');
        $this->line('');

        // Simular datos de webhook de WhatsApp
        $mockData = [
            'messages' => [
                [
                    'id' => 'wamid.test_' . time(),
                    'text' => ['body' => $message],
                    'from' => $number,
                    'timestamp' => now()->timestamp
                ]
            ],
            'contacts' => [
                [
                    'wa_id' => $number,
                    'profile' => ['name' => $name]
                ]
            ]
        ];

        $this->line("📱 Message: {$message}");
        $this->line("👤 Name: {$name}");
        $this->line("📞 Number: {$number}");
        $this->line('');

        try {
            $leadService = new WhatsAppLeadService();
            $results = $leadService->processWhatsAppMessage($mockData);

            if (empty($results)) {
                $this->error('❌ No results returned from lead service');
                return;
            }

            $result = $results[0];

            $this->info('📊 Lead Analysis Results:');
            $this->line('');

            // Mostrar información básica
            $this->line("Message ID: {$result['messageId']}");
            $this->line("From: {$result['fromNumber']}");
            $this->line("Profile: {$result['profileName']}");
            $this->line("Platform: {$result['platform']}");
            $this->line("Timestamp: {$result['timestamp']}");
            $this->line('');

            // Mostrar análisis de lead
            $isHighValue = $result['isHighValueLead'] ? '✅ YES' : '❌ NO';
            $this->line("🎯 High Value Lead: {$isHighValue}");
            $this->line("📈 Lead Score: {$result['leadScore']}/100");
            $this->line('');

            // Mostrar palabras clave detectadas
            if (!empty($result['keywords'])) {
                $this->info('🔍 Keywords Detected:');
                foreach ($result['keywords'] as $category => $keywords) {
                    if (!empty($keywords)) {
                        $this->line("  {$category}: " . implode(', ', $keywords));
                    }
                }
                $this->line('');
            }

            // Mostrar recomendaciones
            if ($result['isHighValueLead']) {
                $this->info('🚀 RECOMMENDED ACTIONS:');
                $this->line('1. Create lead record in Airtable');
                $this->line('2. Send notification to sales team');
                $this->line('3. Add to high-priority follow-up queue');
                $this->line('4. Set up automated response sequence');
            } else {
                $this->warn('⚠️  STANDARD PROCESSING:');
                $this->line('1. Store conversation in Airtable');
                $this->line('2. Continue monitoring for high-value indicators');
                $this->line('3. Apply standard response flow');
            }

            $this->line('');

            // Mostrar detalles técnicos
            if ($this->option('verbose')) {
                $this->info('🔧 Technical Details:');
                $this->line('Message Length: ' . strlen($result['messageText']) . ' characters');
                $this->line('Keywords Count: ' . count($result['keywords'], COUNT_RECURSIVE) - count($result['keywords']));
                $this->line('Lead Score Breakdown:');
                $this->line('  - Message Length: ' . min(strlen($result['messageText']) / 10, 10) . ' points');
                $this->line('  - High-value keywords: ' . (strlen($result['messageText']) > 50 ? '5' : '0') . ' points');
                $this->line('  - Business name indicator: ' . ($this->isBusinessName($result['profileName']) ? '15' : '0') . ' points');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error testing WhatsApp leads: ' . $e->getMessage());
            $this->line('');
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }

    private function isBusinessName(string $name): bool
    {
        $businessIndicators = [
            's.a.', 's.r.l.', 'c.a.', 'ltda', 'inc', 'corp', 'ltd',
            'empresa', 'negocio', 'consultora', 'agencia', 'studio',
            'group', 'solutions', 'services', 'consulting'
        ];

        $nameLower = strtolower($name);
        foreach ($businessIndicators as $indicator) {
            if (strpos($nameLower, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }
}
