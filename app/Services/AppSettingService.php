<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\AppSetting;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Throwable;

class AppSettingService
{
    public function settingsPayload(): array
    {
        $bookingSettings = $this->bookingSettings();
        $branches = $this->activeBranchesPayload();
        $defaultBranchId = (int) ($bookingSettings['default_branch_id'] ?? 0);
        $activeBranchIds = array_map(static fn(array $branch): int => (int) $branch['id'], $branches);

        if (!in_array($defaultBranchId, $activeBranchIds, true)) {
            $defaultBranchId = (int) ($activeBranchIds[0] ?? 0);

            if ($defaultBranchId > 0) {
                $bookingSettings['default_branch_id'] = $defaultBranchId;
                $this->set('booking', $bookingSettings);
            }
        }

        return [
            'default_branch_id' => $defaultBranchId > 0 ? $defaultBranchId : null,
            'branches' => $branches,
        ];
    }

    public function updateDefaultBranch(int $branchId, ?int $userId = null): array
    {
        $branch = Branch::query()
            ->whereKey($branchId)
            ->where('is_active', true)
            ->first();

        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'Selected branch is not active.',
            ]);
        }

        $bookingSettings = $this->bookingSettings();
        $bookingSettings['default_branch_id'] = (int) $branch->id;

        $this->set('booking', $bookingSettings, $userId);

        return $this->settingsPayload();
    }

    public function createBranch(array $payload, ?int $userId = null): array
    {
        $branch = Branch::query()->create([
            'code' => $this->generateBranchCode((string) ($payload['name'] ?? 'BRANCH')),
            'name' => (string) ($payload['name'] ?? 'Branch'),
            'timezone' => (string) ($payload['timezone'] ?? 'Asia/Jakarta'),
            'phone' => !empty($payload['phone']) ? (string) $payload['phone'] : null,
            'address' => !empty($payload['address']) ? (string) $payload['address'] : null,
            'is_active' => true,
        ]);

        $bookingSettings = $this->bookingSettings();

        if ((int) ($bookingSettings['default_branch_id'] ?? 0) <= 0) {
            $bookingSettings['default_branch_id'] = (int) $branch->id;
            $this->set('booking', $bookingSettings, $userId);
        }

        return $this->settingsPayload();
    }

    public function updateBranch(Branch $branch, array $payload): array
    {
        $branch->fill([
            'name' => (string) ($payload['name'] ?? $branch->name),
            'timezone' => (string) ($payload['timezone'] ?? $branch->timezone),
            'phone' => !empty($payload['phone']) ? (string) $payload['phone'] : null,
            'address' => !empty($payload['address']) ? (string) $payload['address'] : null,
        ]);

        $branch->save();

        return $this->settingsPayload();
    }

    public function deactivateBranch(Branch $branch, ?int $userId = null): array
    {
        $activeCount = (int) Branch::query()->where('is_active', true)->count();

        if ($branch->is_active && $activeCount <= 1) {
            throw ValidationException::withMessages([
                'branch' => 'At least one active branch is required.',
            ]);
        }

        $branch->is_active = false;
        $branch->save();

        $bookingSettings = $this->bookingSettings();
        $currentDefaultId = (int) ($bookingSettings['default_branch_id'] ?? 0);

        if ($currentDefaultId === (int) $branch->id) {
            $replacementId = (int) Branch::query()
                ->where('is_active', true)
                ->where('id', '!=', $branch->id)
                ->orderBy('name')
                ->value('id');

            $bookingSettings['default_branch_id'] = $replacementId > 0 ? $replacementId : null;
            $this->set('booking', $bookingSettings, $userId);
        }

        return $this->settingsPayload();
    }

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
            'ui' => $this->get('ui', [
                'admin' => $this->defaultAdminUiConfig(),
                'booking' => $this->defaultBookingUiConfig(),
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

        if (!$setting) {
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
            if (!is_array($value)) {
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
        if (!is_string($value)) {
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

    private function bookingSettings(): array
    {
        return $this->get('booking', [
            'hold_minutes' => 15,
            'arrival_notice_minutes' => 10,
            'queue_board_enabled' => true,
            'default_branch_id' => null,
        ]);
    }

    private function defaultAdminUiConfig(): array
    {
        return [
            'nav_groups' => [
                ['key' => 'overview', 'label' => 'Overview'],
                ['key' => 'management', 'label' => 'Management'],
                ['key' => 'operations', 'label' => 'Operations'],
                ['key' => 'analytics', 'label' => 'Analytics'],
                ['key' => 'system', 'label' => 'System'],
            ],
            'nav_items' => [
                ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'href' => '/admin', 'group' => 'overview'],
                ['id' => 'packages', 'label' => 'Packages', 'icon' => 'package', 'href' => '/admin/packages', 'group' => 'management'],
                ['id' => 'add-ons', 'label' => 'Add-ons', 'icon' => 'package', 'href' => '/admin/add-ons', 'group' => 'management'],
                ['id' => 'stock','label' => 'Stock', 'icon' => 'package', 'href' => '/admin/stock','group' => 'management', ],
                ['id' => 'designs', 'label' => 'Designs', 'icon' => 'palette', 'href' => '/admin/design-catalogs', 'group' => 'management'],
                ['id' => 'branches', 'label' => 'Branches', 'icon' => 'users', 'href' => '/admin/branches', 'group' => 'management'],
                ['id' => 'time-slots', 'label' => 'Time Slots', 'icon' => 'calendar', 'href' => '/admin/time-slots', 'group' => 'management'],
                ['id' => 'blackout-dates', 'label' => 'Blackout Dates', 'icon' => 'calendar', 'href' => '/admin/blackout-dates', 'group' => 'management'],
                ['id' => 'users', 'label' => 'Users', 'icon' => 'users', 'href' => '/admin/users', 'group' => 'management'],
                ['id' => 'bookings', 'label' => 'Bookings', 'icon' => 'calendar', 'href' => '/admin/bookings', 'group' => 'operations'],
                ['id' => 'queue', 'label' => 'Queue', 'icon' => 'list', 'href' => '/admin/queue-tickets', 'group' => 'operations'],
                ['id' => 'transactions', 'label' => 'Transactions', 'icon' => 'receipt', 'href' => '/admin/transactions', 'group' => 'operations'],
                ['id' => 'payments', 'label' => 'Payments', 'icon' => 'receipt', 'href' => '/admin/payments', 'group' => 'operations'],
                ['id' => 'reports', 'label' => 'Reports', 'icon' => 'chart', 'href' => '/admin/reports', 'group' => 'analytics'],
                ['id' => 'activity-logs', 'label' => 'Activity Logs', 'icon' => 'activity', 'href' => '/admin/activity-logs', 'group' => 'analytics'],
                ['id' => 'printer-settings', 'label' => 'Printer Settings', 'icon' => 'settings', 'href' => '/admin/printer-settings', 'group' => 'system'],
                ['id' => 'app-settings', 'label' => 'App Settings', 'icon' => 'settings', 'href' => '/admin/app-settings', 'group' => 'system'],
                ['id' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'href' => '/admin/settings', 'group' => 'system'],
            ],
            'topbar_meta' => [
                'dashboard' => ['title' => 'Dashboard', 'subtitle' => 'Business overview and key metrics'],
                'packages' => ['title' => 'Packages', 'subtitle' => 'Manage your photobooth packages'],
                'add-ons' => ['title' => 'Add-ons', 'subtitle' => 'Manage package add-ons and pricing'],
                'designs' => ['title' => 'Designs', 'subtitle' => 'Photo design templates and themes'],
                'branches' => ['title' => 'Branches', 'subtitle' => 'Manage operational branches'],
                'time-slots' => ['title' => 'Time Slots', 'subtitle' => 'Manage slot availability and capacity'],
                'blackout-dates' => ['title' => 'Blackout Dates', 'subtitle' => 'Control blocked booking dates'],
                'users' => ['title' => 'Users', 'subtitle' => 'Manage staff and customer accounts'],
                'bookings' => ['title' => 'Bookings', 'subtitle' => 'Track and manage all reservations'],
                'queue' => ['title' => 'Queue', 'subtitle' => 'Live session queue management'],
                'transactions' => ['title' => 'Transactions', 'subtitle' => 'Payment history and records'],
                'payments' => ['title' => 'Payments', 'subtitle' => 'Record and review payment entries'],
                'reports' => ['title' => 'Reports', 'subtitle' => 'Business analytics and insights'],
                'activity-logs' => ['title' => 'Activity Logs', 'subtitle' => 'System activity and audit trail'],
                'printer-settings' => ['title' => 'Printer Settings', 'subtitle' => 'Configure printer devices per branch'],
                'app-settings' => ['title' => 'App Settings', 'subtitle' => 'Manage app-wide JSON configuration groups'],
                'settings' => ['title' => 'Settings', 'subtitle' => 'Configure your business preferences'],
            ],
            'booking_filter_tabs' => [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'pending', 'label' => 'Pending'],
                ['key' => 'booked', 'label' => 'Booked'],
                ['key' => 'used', 'label' => 'Completed'],
                ['key' => 'expired', 'label' => 'Cancelled'],
            ],
            'settings_tabs' => [
                ['id' => 'branch', 'label' => 'Branch Setting'],
                ['id' => 'hours', 'label' => 'Operating Hours'],
                ['id' => 'security', 'label' => 'Security'],
            ],
        ];
    }

    private function defaultBookingUiConfig(): array
    {
        return [
            'steps' => ['Paket', 'Tanggal', 'Waktu', 'Add-on'],
            'navigation' => [
                ['key' => 'book', 'label' => 'Book', 'route' => 'booking.customer'],
                ['key' => 'admin', 'label' => 'Admin', 'route' => 'admin.login'],
                ['key' => 'queue', 'label' => 'Queue', 'route' => 'queue.board'],
            ],
        ];
    }

    private function activeBranchesPayload(): array
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'timezone', 'phone', 'address'])
            ->map(static function (Branch $branch): array {
                return [
                    'id' => (int) $branch->id,
                    'code' => (string) $branch->code,
                    'name' => (string) $branch->name,
                    'timezone' => (string) $branch->timezone,
                    'phone' => (string) ($branch->phone ?? ''),
                    'address' => (string) ($branch->address ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function generateBranchCode(string $name): string
    {
        $normalized = strtoupper(Str::of($name)->ascii()->replaceMatches('/[^A-Z0-9]+/', '')->toString());
        $prefix = substr($normalized, 0, 6);
        $prefix = $prefix !== '' ? $prefix : 'BRANCH';

        $index = 1;

        while (true) {
            $candidate = sprintf('%s-%02d', $prefix, $index);

            if (!Branch::query()->where('code', $candidate)->exists()) {
                return $candidate;
            }

            $index++;
        }
    }
}
