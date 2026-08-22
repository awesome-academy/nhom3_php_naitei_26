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

final readonly class ChangeDepartmentLeader
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    public function handle(
        Department $department,
        ?int $leaderId,
        int $version,
        User $actor,
        ?Request $request = null,
    ): Department {
        return DB::transaction(function () use ($department, $leaderId, $version, $actor, $request): Department {
            $lockedDepartment = Department::withTrashed()->lockForUpdate()->findOrFail($department->getKey());
            Gate::forUser($actor)->authorize('changeLeader', $lockedDepartment);
            $this->ensureCurrentVersion($lockedDepartment, $version);

            $newLeader = null;
            if ($leaderId !== null) {
                $newLeader = User::query()
                    ->eligibleDepartmentLeaders()
                    ->lockForUpdate()
                    ->find($leaderId);

                if (! $newLeader) {
                    throw ValidationException::withMessages([
                        'leader_id' => 'Chỉ quản lý đang hoạt động mới có thể làm lãnh đạo phòng ban.',
                    ]);
                }
            }

            $hasMembership = $leaderId !== null
                && DB::table('department_user')
                    ->where('department_id', $lockedDepartment->getKey())
                    ->where('user_id', $leaderId)
                    ->exists();
            $shouldAttachMembership = $leaderId !== null && ! $hasMembership;

            if ($lockedDepartment->leader_id === $leaderId && ! $shouldAttachMembership) {
                return $lockedDepartment;
            }

            $before = $this->activityLogger->departmentSnapshot($lockedDepartment);
            $oldLeader = $lockedDepartment->leader;

            if ($shouldAttachMembership) {
                $lockedDepartment->users()->attach($leaderId);
            }

            $lockedDepartment->leader_id = $leaderId;
            $lockedDepartment->lock_version = (int) $lockedDepartment->lock_version + 1;
            $lockedDepartment->save();
            $lockedDepartment->unsetRelation('leader');

            $after = $this->activityLogger->departmentSnapshot($lockedDepartment);
            $this->activityLogger->record(
                DepartmentActivityLogger::LEADER_CHANGED,
                $lockedDepartment,
                $actor,
                $request,
                before: $before,
                after: $after,
                metadata: [
                    'old_leader' => $oldLeader ? $this->activityLogger->userSnapshot($oldLeader) : null,
                    'new_leader' => $newLeader ? $this->activityLogger->userSnapshot($newLeader) : null,
                    'auto_membership' => $shouldAttachMembership,
                ],
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
