<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class AdminAppSettingService
{
    private const ALLOWED_GROUPS = ['general', 'booking', 'payment', 'ui'];

    public function __construct(
        private readonly AppSettingService $appSettingService,
    ) {}

    public function rows(): array
    {
        return $this->appSettingService->publicSettings();
    }

    public function updateGroup(string $group, array $value, ?int $userId = null): array
    {
        if (! in_array($group, self::ALLOWED_GROUPS, true)) {
            throw ValidationException::withMessages([
                'group' => 'Grup pengaturan tidak dikenal.',
            ]);
        }

        $this->appSettingService->set($group, $value, $userId);

        return $this->rows();
    }
}
