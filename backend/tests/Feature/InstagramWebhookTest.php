<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessInstagramWebhookJob;
use Tests\TestCase;

class InstagramWebhookTest extends TestCase
{
    public function test_webhook_dispatches_job_and_returns_200()
    {
        Queue::fake();

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '12345',
                    'time' => 1234567,
                    'messaging' => [
                        [
                            'sender' => ['id' => '111'],
                            'recipient' => ['id' => '222'],
                            'timestamp' => 1234567,
                            'message' => [
                                'mid' => 'm_123',
                                'text' => 'Hello'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/webhook/instagram', $payload);

        $response->assertStatus(200);
        $response->assertContent('OK');

        Queue::assertPushed(ProcessInstagramWebhookJob::class);
    }
}
