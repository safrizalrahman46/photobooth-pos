<?php

namespace App\Services;

use App\Models\ActivityLog;

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
        ActivityLog::query()->create([
            'actor_id' => $actorId,
            'module' => $module,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
