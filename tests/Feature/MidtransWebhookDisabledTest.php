<?php

namespace Tests\Feature;

use Tests\TestCase;

class MidtransWebhookDisabledTest extends TestCase
{
    public function test_midtrans_webhook_returns_not_found_when_disabled(): void
    {
        $this->postJson('/api/v1/payments/midtrans/notifications', [
            'order_id' => 'BKG-TEST-001',
        ])->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
