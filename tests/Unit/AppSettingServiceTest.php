<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Services\AppSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_use_config_defaults_when_storage_is_unavailable(): void
    {
        config()->set('app.name', 'Ready To Pict');
        config()->set('mail.from.address', 'studio@example.com');
        config()->set('services.midtrans.enabled', true);

        $service = new AppSettingService();
        $settings = $service->publicSettings();

        $this->assertSame('Ready To Pict', $settings['general']['brand_name']);
        $this->assertSame('studio@example.com', $settings['general']['support_email']);
        $this->assertTrue($settings['payment']['midtrans_enabled']);
        $this->assertFalse($settings['payment']['onsite_enabled']);
        $this->assertIsArray($settings['ui']['admin'] ?? null);
        $this->assertIsArray($settings['ui']['booking'] ?? null);
    }

    public function test_settings_payload_uses_first_active_branch_as_default_when_missing(): void
    {
        Branch::query()->create([
            'code' => 'BRANCH-B',
            'name' => 'Beta Branch',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $expectedBranch = Branch::query()->create([
            'code' => 'BRANCH-A',
            'name' => 'Alpha Branch',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $service = new AppSettingService();
        $payload = $service->settingsPayload();

        $this->assertSame($expectedBranch->id, $payload['default_branch_id']);
        $this->assertCount(2, $payload['branches']);
        $this->assertSame($expectedBranch->id, data_get(AppSetting::query()->find('booking'), 'value.default_branch_id'));
    }

    public function test_update_default_branch_persists_booking_setting(): void
    {
        $branch = Branch::query()->create([
            'code' => 'BRANCH-01',
            'name' => 'Primary Branch',
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);

        $service = new AppSettingService();
        $payload = $service->updateDefaultBranch($branch->id, 123);

        $this->assertSame($branch->id, $payload['default_branch_id']);
        $this->assertSame($branch->id, data_get(AppSetting::query()->find('booking'), 'value.default_branch_id'));
        $this->assertSame(123, AppSetting::query()->find('booking')?->updated_by);
    }
}
