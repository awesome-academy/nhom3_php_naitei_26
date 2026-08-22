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

final readonly class TransferDepartmentMember
{
    private const UNAVAILABLE_TARGET = 'Phòng ban đích không khả dụng. Vui lòng chọn lại phòng ban đích.';

    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    public function handle(
        Department $source,
        User $member,
        int $targetId,
        int $sourceVersion,
        int $targetVersion,
        User $actor,
        ?Request $request = null,
    ): Department {
        try {
            return DB::transaction(function () use (
                $source,
                $member,
                $targetId,
                $sourceVersion,
                $targetVersion,
                $actor,
                $request,
            ): Department {
                $departmentIds = [(int) $source->getKey(), $targetId];
                sort($departmentIds, SORT_NUMERIC);

                $lockedDepartments = Department::withTrashed()
                    ->whereKey($departmentIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(fn (Department $department): int => (int) $department->getKey());
                $lockedSource = $lockedDepartments->get((int) $source->getKey());
                $lockedTarget = $lockedDepartments->get($targetId);

                if (! $lockedSource || ! $lockedTarget || $lockedSource->is($lockedTarget)) {
                    throw ValidationException::withMessages([
                        'target_department_id' => self::UNAVAILABLE_TARGET,
                    ]);
                }

                $authorization = Gate::forUser($actor)->inspect('transferMember', [$lockedSource, $lockedTarget]);
                if ($authorization->denied()) {
                    throw ValidationException::withMessages([
                        'target_department_id' => self::UNAVAILABLE_TARGET,
                    ]);
                }

                $this->ensureCurrentVersion($lockedSource, $sourceVersion);
                $this->ensureCurrentVersion($lockedTarget, $targetVersion);

                $lockedMember = User::query()
                    ->eligibleDepartmentStaff()
                    ->lockForUpdate()
                    ->find($member->getKey());
                if (! $lockedMember) {
                    throw ValidationException::withMessages([
                        'member' => 'Chỉ nhân viên đang hoạt động mới có thể được điều chuyển.',
                    ]);
                }

                $sourceMembership = DB::table('department_user')
                    ->where('department_id', $lockedSource->getKey())
                    ->where('user_id', $lockedMember->getKey())
                    ->lockForUpdate()
                    ->first();
                if (! $sourceMembership) {
                    throw ValidationException::withMessages([
                        'member' => 'Nhân viên không còn thuộc phòng ban nguồn. Vui lòng tải lại trang.',
                    ]);
                }

                if (DB::table('department_user')
                    ->where('department_id', $lockedTarget->getKey())
                    ->where('user_id', $lockedMember->getKey())
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'target_department_id' => self::UNAVAILABLE_TARGET,
                    ]);
                }

                $sourceBefore = $this->activityLogger->departmentSnapshot($lockedSource);
                $targetBefore = $this->activityLogger->departmentSnapshot($lockedTarget);

                $lockedTarget->users()->attach($lockedMember->getKey());
                $lockedSource->users()->detach($lockedMember->getKey());
                $lockedSource->lock_version = (int) $lockedSource->lock_version + 1;
                $lockedTarget->lock_version = (int) $lockedTarget->lock_version + 1;
                $lockedSource->save();
                $lockedTarget->save();

                $sourceAfter = $this->activityLogger->departmentSnapshot($lockedSource);
                $targetAfter = $this->activityLogger->departmentSnapshot($lockedTarget);
                $this->activityLogger->record(
                    DepartmentActivityLogger::MEMBER_TRANSFERRED,
                    $lockedSource,
                    $actor,
                    $request,
                    before: $sourceBefore,
                    after: $sourceAfter,
                    metadata: [
                        'member' => $this->activityLogger->userSnapshot($lockedMember),
                        'source' => ['before' => $sourceBefore, 'after' => $sourceAfter],
                        'target' => ['before' => $targetBefore, 'after' => $targetAfter],
                    ],
                );

                return $lockedTarget;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'target_department_id' => self::UNAVAILABLE_TARGET,
            ]);
        }
    }

    private function ensureCurrentVersion(Department $department, int $expectedVersion): void
    {
        $actualVersion = (int) $department->lock_version;
        if ($actualVersion !== $expectedVersion) {
            throw new StaleDepartmentVersion(
                (int) $department->getKey(),
                $expectedVersion,
                $actualVersion,
            );
        }
    }
}
