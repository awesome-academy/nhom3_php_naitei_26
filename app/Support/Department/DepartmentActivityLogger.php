<?php

namespace App\Support\Department;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

final class DepartmentActivityLogger
{
    public const CREATED = 'department.created';

    public const UPDATED = 'department.updated';

    public const ARCHIVED = 'department.archived';

    public const LEADER_CHANGED = 'department.leader_changed';

    public const MEMBER_ADDED = 'department.member_added';

    public const MEMBER_REMOVED = 'department.member_removed';

    public const MEMBER_TRANSFERRED = 'department.member_transferred';

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        Department $department,
        User $actor,
        ?Request $request = null,
        ?array $before = null,
        ?array $after = null,
        array $metadata = [],
    ): ActivityLog {
        $eventMetadata = [
            ...$metadata,
            'actor' => $this->userSnapshot($actor),
            'department' => $this->departmentSnapshot($department),
        ];

        if ($before !== null) {
            $eventMetadata['before'] = $before;
        }

        if ($after !== null) {
            $eventMetadata['after'] = $after;
        }

        return ActivityLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => $department->getMorphClass(),
            'subject_id' => $department->getKey(),
            'description' => $this->descriptionFor($action),
            'metadata' => $eventMetadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentSnapshot(Department $department): array
    {
        $department->loadMissing('leader');

        return [
            'id' => $department->getKey(),
            'name' => $department->name,
            'code' => $department->code,
            'address' => $department->address,
            'leader' => $department->leader === null
                ? null
                : $this->userSnapshot($department->leader),
            'lock_version' => $department->lock_version,
            'archived_at' => $department->deleted_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userSnapshot(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
            'archived_at' => $user->deleted_at?->toISOString(),
        ];
    }

    private function descriptionFor(string $action): string
    {
        return match ($action) {
            self::CREATED => 'Đã tạo phòng ban.',
            self::UPDATED => 'Đã cập nhật phòng ban.',
            self::ARCHIVED => 'Đã lưu trữ phòng ban.',
            self::LEADER_CHANGED => 'Đã thay đổi lãnh đạo phòng ban.',
            self::MEMBER_ADDED => 'Đã thêm thành viên vào phòng ban.',
            self::MEMBER_REMOVED => 'Đã gỡ thành viên khỏi phòng ban.',
            self::MEMBER_TRANSFERRED => 'Đã điều chuyển thành viên sang phòng ban khác.',
            default => 'Đã thay đổi cơ cấu phòng ban.',
        };
    }
}
