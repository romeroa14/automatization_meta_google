<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessWhatsAppWebhookJob;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    public function test_webhook_dispatches_job_and_returns_200()
    {
        Queue::fake();

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '12345',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['display_phone_number' => '123', 'phone_number_id' => '123'],
                                'messages' => [
                                    ['from' => '456', 'id' => 'wamid.123', 'timestamp' => '123', 'type' => 'text', 'text' => ['body' => 'Hello']]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);

        $response->assertStatus(200);
        $response->assertContent('OK');

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    }
}
