<?php

namespace Tests\Unit;

use App\Services\MidtransService;
use Tests\TestCase;

class MidtransServiceTest extends TestCase
{
    public function test_verify_notification_signature_returns_true_for_valid_signature(): void
    {
        config()->set('services.midtrans.server_key', 'server-key-test');

        $payload = [
            'order_id' => 'BKG-20260419-0001',
            'status_code' => '200',
            'gross_amount' => '150000.00',
        ];

        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-key-test');

        $service = new MidtransService();

        $this->assertTrue($service->verifyNotificationSignature($payload));
    }

    public function test_verify_notification_signature_returns_false_for_invalid_signature(): void
    {
        config()->set('services.midtrans.server_key', 'server-key-test');

        $service = new MidtransService();

        $this->assertFalse($service->verifyNotificationSignature([
            'order_id' => 'BKG-20260419-0001',
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => 'invalid-signature',
        ]));
    }
}
