<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Arr;
use Throwable;

class AppSettingService
{
    public function publicSettings(): array
    {
        return [
            'general' => $this->get('general', [
                'brand_name' => config('app.name', 'Ready To Pict'),
                'short_name' => 'Ready To Pict',
                'tagline' => 'Photo booth cepat, estetik, dan anti ribet.',
                'support_email' => config('mail.from.address'),
                'support_phone' => '',
                'address' => '',
                'logo_url' => '/favicon.ico',
            ]),
            'booking' => $this->get('booking', [
                'hold_minutes' => 15,
                'arrival_notice_minutes' => 10,
                'queue_board_enabled' => true,
            ]),
            'payment' => $this->get('payment', [
                'onsite_enabled' => true,
                'midtrans_enabled' => (bool) config('services.midtrans.enabled', false),
                'currency' => 'IDR',
            ]),
        ];
    }

    public function get(string $key, array $defaults = []): array
    {
        try {
            $setting = AppSetting::query()->find($key);
        } catch (Throwable) {
            return $defaults;
        }

        if (! $setting) {
            return $defaults;
        }

        $value = is_array($setting->value) ? $this->normalizeArray($setting->value) : [];

        return array_replace_recursive($defaults, $value);
    }

    public function set(string $key, array $value, ?int $userId = null): AppSetting
    {
        return AppSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => Arr::undot(Arr::dot($value)),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]
        );
    }

    public function setMany(array $groups, ?int $userId = null): array
    {
        $stored = [];

        foreach ($groups as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $stored[$key] = $this->set((string) $key, $value, $userId)->value;
        }

        return $stored;
    }

    private function normalizeArray(array $value): array
    {
        $normalized = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $normalized[$key] = $this->normalizeArray($item);

                continue;
            }

            $normalized[$key] = $this->normalizeScalar($item);
        }

        return $normalized;
    }

    private function normalizeScalar(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return match (strtolower($trimmed)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($trimmed)
                ? (str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed)
                : $value,
        };
    }
}
