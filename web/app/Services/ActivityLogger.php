<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(
        string $module,
        string $action,
        ?int $actorId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $properties = []
    ): void {
        $resolvedActorId = $actorId && $actorId > 0
            ? $actorId
            : (Auth::id() ? (int) Auth::id() : null);

        $request = app()->bound('request') ? request() : null;

        ActivityLog::query()->create([
            'actor_id' => $resolvedActorId,
            'module' => $module,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function purgeOlderThanDays(int $days): int
    {
        $retentionDays = max(1, $days);

        return ActivityLog::query()
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();
    }

    public function recentRows(int $limit = 100): array
    {
        return ActivityLog::query()
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(max(1, min($limit, 500)))
            ->get(['id', 'actor_id', 'action', 'module', 'subject_type', 'subject_id', 'properties', 'created_at'])
            ->map(function (ActivityLog $log): array {
                $properties = is_array($log->properties) ? $log->properties : [];

                return [
                    'id' => (int) $log->id,
                    'actor' => (string) ($log->actor?->name ?? 'System'),
                    'action' => $this->formatActivityToken((string) $log->action),
                    'action_key' => (string) $log->action,
                    'module' => $this->formatActivityToken((string) ($log->module ?: '-')),
                    'module_key' => (string) ($log->module ?: '-'),
                    'label' => (string) ($properties['label'] ?? ''),
                    'message' => (string) ($properties['message'] ?? $this->formatActivityToken((string) $log->action)),
                    'details' => $this->activityDetailLines($properties),
                    'changed_fields' => $this->activityChangedFields($properties),
                    'time' => $log->created_at?->diffForHumans() ?? '-',
                    'time_text' => $log->created_at?->translatedFormat('d M Y, H:i') ?? '-',
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    protected function formatActivityToken(string $value): string
    {
        $normalized = trim(str_replace(['-', '_'], ' ', $value));

        return $normalized !== '' ? ucwords($normalized) : '-';
    }

    protected function activityChangedFields(array $properties): array
    {
        $fields = $properties['updated_fields'] ?? $properties['changed_fields'] ?? [];

        if (! is_array($fields)) {
            return [];
        }

        return collect($fields)
            ->map(fn ($field): string => $this->formatActivityToken((string) $field))
            ->filter(fn (string $field): bool => $field !== '-')
            ->take(6)
            ->values()
            ->all();
    }

    protected function activityDetailLines(array $properties): array
    {
        $details = [];

        if (! empty($properties['customer_name'])) {
            $details[] = 'Customer: ' . (string) $properties['customer_name'];
        }

        if (! empty($properties['transaction_code'])) {
            $details[] = 'Transaksi: ' . (string) $properties['transaction_code'];
        }

        if (! empty($properties['booking_date'])) {
            $details[] = 'Tanggal booking: ' . (string) $properties['booking_date'];
        }

        if (! empty($properties['queue_date'])) {
            $details[] = 'Tanggal antrean: ' . (string) $properties['queue_date'];
        }

        if (! empty($properties['method'])) {
            $details[] = 'Metode: ' . strtoupper((string) $properties['method']);
        }

        if (array_key_exists('amount', $properties)) {
            $details[] = 'Nominal: ' . $this->formatRupiah((float) $properties['amount']);
        }

        if (! empty($properties['from_status']) || ! empty($properties['to_status'])) {
            $details[] = sprintf(
                'Status: %s -> %s',
                (string) ($properties['from_status'] ?? '-'),
                (string) ($properties['to_status'] ?? '-'),
            );
        }

        if (! empty($properties['notes'])) {
            $details[] = 'Catatan: ' . (string) $properties['notes'];
        }

        return array_slice($details, 0, 4);
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
