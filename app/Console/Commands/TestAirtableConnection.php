<?php

namespace App\Console\Commands;

use App\Services\WhatsAppLeadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAirtableConnection extends Command
{
    protected $signature = 'airtable:test-connection';
    protected $description = 'Test Airtable connection and API access';

    public function handle()
    {
        $this->info('🔗 Testing Airtable Connection...');
        $this->line('');

        // Verificar configuración
        $apiKey = config('services.airtable.api_key');
        $baseId = config('services.airtable.base_id');

        if (!$apiKey) {
            $this->error('❌ AIRTABLE_API_KEY not configured in .env file');
            return;
        }

        if (!$baseId) {
            $this->error('❌ AIRTABLE_BASE_ID not configured in .env file');
            return;
        }

        $this->line("API Key: " . substr($apiKey, 0, 10) . "...");
        $this->line("Base ID: {$baseId}");
        $this->line('');

        try {
            // Probar conexión básica
            $this->info('1. Testing basic connection...');
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])->get("https://api.airtable.com/v0/{$baseId}/Conversations", [
                'maxRecords' => 1
            ]);

            if ($response->successful()) {
                $this->info('✅ Basic connection successful');
                $data = $response->json();
                $this->line("   Records found: " . count($data['records']));
            } else {
                $this->error('❌ Basic connection failed');
                $this->line("   Status: " . $response->status());
                $this->line("   Response: " . $response->body());
                return;
            }

            $this->line('');

            // Probar búsqueda con fórmula
            $this->info('2. Testing search with formula...');
            $testUserId = 'test_user_' . time();
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])->get("https://api.airtable.com/v0/{$baseId}/Conversations", [
                'filterByFormula' => "{User ID} = '{$testUserId}'",
                'maxRecords' => 1
            ]);

            if ($response->successful()) {
                $this->info('✅ Search with formula successful');
                $data = $response->json();
                $this->line("   Records found: " . count($data['records']));
            } else {
                $this->error('❌ Search with formula failed');
                $this->line("   Status: " . $response->status());
                $this->line("   Response: " . $response->body());
            }

            $this->line('');

            // Probar creación de registro
            $this->info('3. Testing record creation...');
            $testData = [
                'fields' => [
                    'Message ID' => 'test_' . time(),
                    'User ID' => $testUserId,
                    'Message Text' => 'Test message for connection verification',
                    'Timestamp' => now()->toISOString(),
                    'Platform' => 'whatsapp',
                    'Status' => 'received',
                    'Message Length' => 50,
                    'Is Client Message' => true
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])->post("https://api.airtable.com/v0/{$baseId}/Conversations", $testData);

            if ($response->successful()) {
                $this->info('✅ Record creation successful');
                $data = $response->json();
                $recordId = $data['id'];
                $this->line("   Record ID: {$recordId}");

                // Limpiar registro de prueba
                $this->line('   Cleaning up test record...');
                $deleteResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json'
                ])->delete("https://api.airtable.com/v0/{$baseId}/Conversations/{$recordId}");

                if ($deleteResponse->successful()) {
                    $this->line('   ✅ Test record cleaned up');
                } else {
                    $this->warn('   ⚠️  Could not clean up test record');
                }
            } else {
                $this->error('❌ Record creation failed');
                $this->line("   Status: " . $response->status());
                $this->line("   Response: " . $response->body());
            }

            $this->line('');

            // Probar servicio de leads
            $this->info('4. Testing WhatsApp Lead Service...');
            $leadService = new WhatsAppLeadService();
            
            $mockData = [
                'messages' => [
                    [
                        'id' => 'test_' . time(),
                        'text' => ['body' => 'Necesito información sobre sus servicios de marketing digital. ¿Cuál es el precio?'],
                        'from' => '+584241234567',
                        'timestamp' => now()->timestamp
                    ]
                ],
                'contacts' => [
                    [
                        'wa_id' => '+584241234567',
                        'profile' => ['name' => 'Empresa Test S.A.']
                    ]
                ]
            ];

            $results = $leadService->processWhatsAppMessage($mockData);
            
            if (!empty($results)) {
                $result = $results[0];
                $this->info('✅ WhatsApp Lead Service working');
                $this->line("   High Value Lead: " . ($result['isHighValueLead'] ? 'YES' : 'NO'));
                $this->line("   Lead Score: {$result['leadScore']}/100");
                $this->line("   Keywords: " . count($result['keywords'], COUNT_RECURSIVE) - count($result['keywords']));
            } else {
                $this->error('❌ WhatsApp Lead Service failed');
            }

            $this->line('');
            $this->info('🎉 All tests completed successfully!');
            $this->line('');
            $this->line('Next steps:');
            $this->line('1. Configure N8N webhook URL in .env');
            $this->line('2. Test the complete flow with: php artisan whatsapp:test-leads');
            $this->line('3. Monitor logs for lead detection');

        } catch (\Exception $e) {
            $this->error('❌ Error testing Airtable connection: ' . $e->getMessage());
            $this->line('');
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
