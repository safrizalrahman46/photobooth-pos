<?php

namespace App\Services;

use App\Models\AddOn;

class AdminAddOnService
{
    public function create(array $payload): AddOn
    {
        return AddOn::query()->create([
            'package_id' => $payload['package_id'] ?? null,
            'code' => trim((string) ($payload['code'] ?? '')) ?: $this->nextCode(),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'price' => $payload['price'],
            'max_qty' => $payload['max_qty'],
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);
    }

    public function update(AddOn $addOn, array $payload): AddOn
    {
        $codeInput = trim((string) ($payload['code'] ?? ''));

        $addOn->update([
            'package_id' => array_key_exists('package_id', $payload) ? $payload['package_id'] : $addOn->package_id,
            'code' => $codeInput !== '' ? $codeInput : $addOn->code,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'price' => $payload['price'],
            'max_qty' => $payload['max_qty'],
            'is_active' => $payload['is_active'] ?? $addOn->is_active,
            'sort_order' => $payload['sort_order'] ?? $addOn->sort_order,
        ]);

        return $addOn->refresh();
    }

    public function delete(AddOn $addOn): void
    {
        $addOn->delete();
    }

    private function nextCode(): string
    {
        $cursor = ((int) AddOn::query()->max('id')) + 1;

        do {
            $candidate = sprintf('ADDON-%05d', $cursor);
            $exists = AddOn::query()->where('code', $candidate)->exists();
            $cursor++;
        } while ($exists);

        return $candidate;
    }
}
