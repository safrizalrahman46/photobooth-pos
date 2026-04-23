<?php

namespace App\Services;

use App\Models\PrinterSetting;

class AdminPrinterSettingService
{
    public function rows(array $filters = []): array
    {
        $query = PrinterSetting::query()
            ->with('branch:id,name')
            ->orderByDesc('is_default')
            ->orderBy('device_name');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['printer_type'])) {
            $query->where('printer_type', (string) $filters['printer_type']);
        }

        if (! ($filters['include_inactive'] ?? false)) {
            $query->where('is_active', true);
        }

        return $query
            ->get()
            ->map(fn (PrinterSetting $setting): array => $this->mapRow($setting))
            ->values()
            ->all();
    }

    public function create(array $payload): PrinterSetting
    {
        $isDefault = (bool) ($payload['is_default'] ?? false);
        $branchId = (int) $payload['branch_id'];

        if ($isDefault) {
            $this->clearDefaultOnBranch($branchId);
        }

        $setting = PrinterSetting::query()->create([
            'branch_id' => $branchId,
            'device_name' => (string) $payload['device_name'],
            'printer_type' => (string) $payload['printer_type'],
            'connection' => is_array($payload['connection'] ?? null) ? $payload['connection'] : null,
            'paper_width_mm' => (int) $payload['paper_width_mm'],
            'is_default' => $isDefault,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        if (! $isDefault && ! $this->hasDefaultOnBranch($branchId, (int) $setting->id)) {
            $setting->is_default = true;
            $setting->save();
        }

        return $setting->refresh();
    }

    public function update(PrinterSetting $printerSetting, array $payload): PrinterSetting
    {
        $branchId = (int) $payload['branch_id'];
        $isDefault = (bool) ($payload['is_default'] ?? false);

        if ($isDefault) {
            $this->clearDefaultOnBranch($branchId, (int) $printerSetting->id);
        }

        $printerSetting->fill([
            'branch_id' => $branchId,
            'device_name' => (string) $payload['device_name'],
            'printer_type' => (string) $payload['printer_type'],
            'connection' => is_array($payload['connection'] ?? null) ? $payload['connection'] : null,
            'paper_width_mm' => (int) $payload['paper_width_mm'],
            'is_default' => $isDefault,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $printerSetting->save();

        if (! $isDefault && ! $this->hasDefaultOnBranch($branchId, (int) $printerSetting->id)) {
            $printerSetting->is_default = true;
            $printerSetting->save();
        }

        return $printerSetting->refresh();
    }

    public function setDefault(PrinterSetting $printerSetting): PrinterSetting
    {
        $this->clearDefaultOnBranch((int) $printerSetting->branch_id, (int) $printerSetting->id);

        $printerSetting->is_default = true;
        $printerSetting->save();

        return $printerSetting->refresh();
    }

    public function destroy(PrinterSetting $printerSetting): void
    {
        $branchId = (int) $printerSetting->branch_id;
        $wasDefault = (bool) $printerSetting->is_default;

        $printerSetting->delete();

        if (! $wasDefault) {
            return;
        }

        $replacement = PrinterSetting::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('device_name')
            ->first();

        if (! $replacement) {
            return;
        }

        $replacement->is_default = true;
        $replacement->save();
    }

    private function clearDefaultOnBranch(int $branchId, ?int $ignoreId = null): void
    {
        $query = PrinterSetting::query()
            ->where('branch_id', $branchId)
            ->where('is_default', true);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $query->update([
            'is_default' => false,
            'updated_at' => now(),
        ]);
    }

    private function hasDefaultOnBranch(int $branchId, ?int $includeId = null): bool
    {
        $query = PrinterSetting::query()
            ->where('branch_id', $branchId)
            ->where('is_default', true);

        if ($includeId !== null) {
            $query->orWhere(function ($nested) use ($branchId, $includeId): void {
                $nested->where('branch_id', $branchId)
                    ->whereKey($includeId)
                    ->where('is_default', true);
            });
        }

        return $query->exists();
    }

    private function mapRow(PrinterSetting $setting): array
    {
        return [
            'id' => (int) $setting->id,
            'branch_id' => (int) $setting->branch_id,
            'branch_name' => (string) ($setting->branch?->name ?? '-'),
            'device_name' => (string) $setting->device_name,
            'printer_type' => (string) $setting->printer_type,
            'connection' => is_array($setting->connection) ? $setting->connection : [],
            'paper_width_mm' => (int) $setting->paper_width_mm,
            'is_default' => (bool) $setting->is_default,
            'is_active' => (bool) $setting->is_active,
            'created_at' => $setting->created_at?->toIso8601String(),
            'updated_at' => $setting->updated_at?->toIso8601String(),
        ];
    }
}

