<?php

namespace App\Services;

use App\Models\DesignCatalog;

class AdminDesignService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function create(array $payload): DesignCatalog
    {
        $design = DesignCatalog::query()->create([
            'package_id' => $payload['package_id'] ?? null,
            'code' => $this->nextCode(),
            'name' => $payload['name'],
            'theme' => $payload['theme'] ?? null,
            'preview_url' => $payload['preview_url'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        $this->activityLogger->log(
            'designs',
            'created',
            null,
            DesignCatalog::class,
            (int) $design->id,
            [
                'message' => sprintf('Design %s dibuat.', (string) $design->name),
                'label' => (string) $design->code,
                'name' => (string) $design->name,
                'package_id' => $design->package_id ? (int) $design->package_id : null,
            ],
        );

        return $design;
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

        $this->activityLogger->log(
            'designs',
            'updated',
            null,
            DesignCatalog::class,
            (int) $designCatalog->id,
            [
                'message' => sprintf('Design %s diperbarui.', (string) $designCatalog->name),
                'label' => (string) $designCatalog->code,
                'name' => (string) $designCatalog->name,
                'package_id' => $designCatalog->package_id ? (int) $designCatalog->package_id : null,
                'updated_fields' => array_keys($payload),
            ],
        );

        return $designCatalog->refresh();
    }

    public function delete(DesignCatalog $designCatalog): void
    {
        $this->activityLogger->log(
            'designs',
            'deleted',
            null,
            DesignCatalog::class,
            (int) $designCatalog->id,
            [
                'message' => sprintf('Design %s dihapus.', (string) $designCatalog->name),
                'label' => (string) $designCatalog->code,
                'name' => (string) $designCatalog->name,
            ],
        );

        $designCatalog->delete();
    }

    public function managementRows(): array
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->toDateString();

        return DesignCatalog::query()
            ->with(['package:id,name'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount([
                'bookings as total_bookings',
                'bookings as this_month_bookings' => function ($query) use ($startOfMonth, $endOfMonth): void {
                    $query->whereBetween('booking_date', [$startOfMonth, $endOfMonth]);
                },
            ])
            ->get([
                'id',
                'package_id',
                'code',
                'name',
                'theme',
                'preview_url',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (DesignCatalog $design): array {
                return [
                    'id' => (int) $design->id,
                    'package_id' => $design->package_id ? (int) $design->package_id : null,
                    'package_name' => (string) ($design->package?->name ?? '-'),
                    'code' => (string) $design->code,
                    'name' => (string) $design->name,
                    'theme' => (string) ($design->theme ?? ''),
                    'preview_url' => (string) ($design->preview_url ?? ''),
                    'is_active' => (bool) $design->is_active,
                    'sort_order' => (int) $design->sort_order,
                    'total_bookings' => (int) ($design->total_bookings ?? 0),
                    'this_month_bookings' => (int) ($design->this_month_bookings ?? 0),
                    'created_at' => $design->created_at?->toIso8601String(),
                    'updated_at' => $design->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
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
