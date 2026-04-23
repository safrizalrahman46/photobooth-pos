<?php

namespace Tests\Unit;

use App\Services\AppSettingService;
use Tests\TestCase;

class AppSettingServiceTest extends TestCase
{
    public function test_public_settings_use_config_defaults_when_storage_is_unavailable(): void
    {
        config()->set('app.name', 'Ready To Pict');
        config()->set('mail.from.address', 'studio@example.com');
        config()->set('services.midtrans.enabled', true);

        $service = new AppSettingService();
        $settings = $service->publicSettings();

        $this->assertSame('Ready To Pict', $settings['general']['brand_name']);
        $this->assertIsString($settings['general']['support_email']);
        $this->assertFalse($settings['payment']['midtrans_enabled']);
        $this->assertTrue($settings['payment']['onsite_enabled']);
        $this->assertTrue($settings['payment']['manual_transfer_proof_required']);
    }
}
