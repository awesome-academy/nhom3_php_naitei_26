<?php

namespace App\Support\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AuthEventLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?User $actor = null,
        ?Model $subject = null,
        ?Request $request = null,
        ?string $description = null,
        array $metadata = [],
    ): ActivityLog {
        return ActivityLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function accessDenied(
        ?User $actor,
        Request $request,
        string $reason,
        array $metadata = [],
    ): ActivityLog {
        return $this->log(
            action: 'access.denied',
            actor: $actor,
            request: $request,
            description: 'Đã từ chối truy cập vào khu vực được bảo vệ.',
            metadata: [
                'reason' => $reason,
                'path' => $request->path(),
                'method' => $request->method(),
                ...$metadata,
            ],
        );
    }
}
