<?php

namespace App\Actions\Department;

use App\Exceptions\StaleDepartmentVersion;
use App\Models\Department;
use App\Models\User;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class AddDepartmentMember
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    public function handle(
        Department $department,
        int $userId,
        int $version,
        User $actor,
        ?Request $request = null,
    ): Department {
        try {
            return DB::transaction(function () use ($department, $userId, $version, $actor, $request): Department {
                $lockedDepartment = Department::withTrashed()->lockForUpdate()->findOrFail($department->getKey());
                Gate::forUser($actor)->authorize('addMember', $lockedDepartment);
                $this->ensureCurrentVersion($lockedDepartment, $version);

                $member = User::query()
                    ->availableDepartmentStaff()
                    ->lockForUpdate()
                    ->find($userId);

                if (! $member) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Chỉ nhân viên đang hoạt động và chưa được phân công vào phòng ban nào mới có thể được thêm.',
                    ]);
                }

                $before = $this->activityLogger->departmentSnapshot($lockedDepartment);
                $lockedDepartment->users()->attach($member->getKey());
                $lockedDepartment->lock_version = (int) $lockedDepartment->lock_version + 1;
                $lockedDepartment->save();
                $after = $this->activityLogger->departmentSnapshot($lockedDepartment);

                $this->activityLogger->record(
                    DepartmentActivityLogger::MEMBER_ADDED,
                    $lockedDepartment,
                    $actor,
                    $request,
                    before: $before,
                    after: $after,
                    metadata: ['member' => $this->activityLogger->userSnapshot($member)],
                );

                return $lockedDepartment;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'user_id' => 'Nhân viên này đã được phân công vào phòng ban.',
            ]);
        }
    }

    private function ensureCurrentVersion(Department $department, int $version): void
    {
        $actualVersion = (int) $department->lock_version;
        if ($actualVersion !== $version) {
            throw new StaleDepartmentVersion((int) $department->getKey(), $version, $actualVersion);
        }
    }
}
