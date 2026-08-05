<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function log(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): ActivityLog {
        $user ??= auth()->user();

        $request = request();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => empty($properties) ? null : $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
