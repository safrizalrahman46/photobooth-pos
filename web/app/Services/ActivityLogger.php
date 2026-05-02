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
}
