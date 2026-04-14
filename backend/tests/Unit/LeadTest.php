<?php

namespace Tests\Unit;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_bot_respond_returns_false_if_bot_disabled()
    {
        $lead = new Lead();
        $lead->bot_disabled = true;

        $this->assertFalse($lead->canBotRespond());
    }

    public function test_can_bot_respond_returns_false_if_recent_human_intervention()
    {
        $lead = new Lead();
        $lead->bot_disabled = false;
        $lead->last_human_intervention_at = now()->subHours(2); // Less than 24 hours

        $this->assertFalse($lead->canBotRespond());
    }

    public function test_can_bot_respond_returns_true_if_past_human_intervention_window()
    {
        $lead = new Lead();
        $lead->bot_disabled = false;
        $lead->last_human_intervention_at = now()->subHours(25); // More than 24 hours

        $this->assertTrue($lead->canBotRespond());
    }

    public function test_can_bot_respond_returns_true_if_no_human_intervention()
    {
        $lead = new Lead();
        $lead->bot_disabled = false;
        $lead->last_human_intervention_at = null;

        $this->assertTrue($lead->canBotRespond());
    }

    public function test_should_send_to_n8n_returns_same_as_can_bot_respond()
    {
        $lead = new Lead();
        $lead->bot_disabled = false;
        $lead->last_human_intervention_at = null;

        $this->assertTrue($lead->shouldSendToN8n());

        $lead->bot_disabled = true;
        $this->assertFalse($lead->shouldSendToN8n());
    }
}
