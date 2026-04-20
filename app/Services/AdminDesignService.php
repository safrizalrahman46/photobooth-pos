<?php

namespace App\Services;

use App\Models\DesignCatalog;

class AdminDesignService
{
    public function create(array $payload): DesignCatalog
    {
        return DesignCatalog::query()->create([
            'package_id' => $payload['package_id'] ?? null,
            'code' => $this->nextCode(),
            'name' => $payload['name'],
            'theme' => $payload['theme'] ?? null,
            'preview_url' => $payload['preview_url'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);
    }

    public function update(DesignCatalog $designCatalog, array $payload): DesignCatalog
    {
        $designCatalog->update([
            'package_id' => array_key_exists('package_id', $payload) ? $payload['package_id'] : $designCatalog->package_id,
            'name' => $payload['name'],
            'theme' => $payload['theme'] ?? null,
            'preview_url' => $payload['preview_url'] ?? null,
            'is_active' => $payload['is_active'] ?? $designCatalog->is_active,
            'sort_order' => $payload['sort_order'] ?? $designCatalog->sort_order,
        ]);

        return $designCatalog->refresh();
    }

    public function delete(DesignCatalog $designCatalog): void
    {
        $designCatalog->delete();
    }

    private function nextCode(): string
    {
        $cursor = ((int) DesignCatalog::withTrashed()->max('id')) + 1;

        do {
            $candidate = sprintf('DSG-%05d', $cursor);
            $exists = DesignCatalog::withTrashed()->where('code', $candidate)->exists();
            $cursor++;
        } while ($exists);

        return $candidate;
    }
}
