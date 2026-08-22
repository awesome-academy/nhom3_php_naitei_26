<?php

namespace App\Actions\Department;

use App\Exceptions\StaleDepartmentVersion;
use App\Models\Department;
use App\Models\User;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class RemoveDepartmentMember
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    public function handle(
        Department $department,
        User $member,
        int $version,
        User $actor,
        ?Request $request = null,
    ): Department {
        return DB::transaction(function () use ($department, $member, $version, $actor, $request): Department {
            $lockedDepartment = Department::withTrashed()->lockForUpdate()->findOrFail($department->getKey());
            Gate::forUser($actor)->authorize('removeMember', $lockedDepartment);
            $this->ensureCurrentVersion($lockedDepartment, $version);

            $lockedMember = User::withTrashed()->lockForUpdate()->findOrFail($member->getKey());
            if ($actor->isManager() && ! $lockedMember->isStaff()) {
                throw ValidationException::withMessages([
                    'member' => 'Quản lý chỉ có thể gỡ nhân viên khỏi phòng ban mình lãnh đạo.',
                ]);
            }

            if ($lockedDepartment->leader_id === $lockedMember->getKey()) {
                throw ValidationException::withMessages([
                    'member' => 'Không thể gỡ lãnh đạo hiện tại. Vui lòng thay đổi hoặc bỏ chỉ định lãnh đạo trước.',
                ]);
            }

            $membership = DB::table('department_user')
                ->where('department_id', $lockedDepartment->getKey())
                ->where('user_id', $lockedMember->getKey())
                ->lockForUpdate()
                ->first();
            if (! $membership) {
                throw ValidationException::withMessages([
                    'member' => 'Thành viên không còn thuộc phòng ban. Vui lòng tải lại trang.',
                ]);
            }

            $before = $this->activityLogger->departmentSnapshot($lockedDepartment);
            $lockedDepartment->users()->detach($lockedMember->getKey());
            $lockedDepartment->lock_version = (int) $lockedDepartment->lock_version + 1;
            $lockedDepartment->save();
            $after = $this->activityLogger->departmentSnapshot($lockedDepartment);

            $this->activityLogger->record(
                DepartmentActivityLogger::MEMBER_REMOVED,
                $lockedDepartment,
                $actor,
                $request,
                before: $before,
                after: $after,
                metadata: ['member' => $this->activityLogger->userSnapshot($lockedMember)],
            );

            return $lockedDepartment;
        });
    }

    private function ensureCurrentVersion(Department $department, int $version): void
    {
        $actualVersion = (int) $department->lock_version;
        if ($actualVersion !== $version) {
            throw new StaleDepartmentVersion((int) $department->getKey(), $version, $actualVersion);
        }
    }
}
