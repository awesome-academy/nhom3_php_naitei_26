<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\User;
use App\Support\Application\ApplicationActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class AssignApplicationAction
{
    public function __construct(
        private ApplicationActivityLogger $activityLogger,
    ) {}

    public function handle(Application $application, User $staff, User $actor, ?string $note = null): Application
    {
        return DB::transaction(function () use ($application, $staff, $actor, $note): Application {
            $lockedStaff = User::query()->lockForUpdate()->find($staff->getKey());

            if ($lockedStaff === null || ! $lockedStaff->isStaff() || ! $lockedStaff->canAccessProtectedResources()) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Nhân viên phải đang hoạt động để nhận hồ sơ.',
                ]);
            }

            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            Gate::forUser($actor)->authorize('assign', $locked);

            if (in_array($locked->status, [
                ApplicationStatus::Approved,
                ApplicationStatus::Rejected,
            ], true)) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Không thể phân công hồ sơ đã hoàn tất.',
                ]);
            }

            $serviceType = $locked->serviceType;

            if ($serviceType === null) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Hồ sơ không hợp lệ.',
                ]);
            }

            $isEligible = $lockedStaff->departments()
                ->whereKey($serviceType->responsible_department_id)
                ->exists();

            if (! $isEligible) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Nhân viên phải đang hoạt động và thuộc phòng ban phụ trách dịch vụ của hồ sơ.',
                ]);
            }

            $this->closeActiveAssignments($locked);

            ApplicationAssignment::query()->create([
                'application_id' => $locked->getKey(),
                'staff_id' => $lockedStaff->getKey(),
                'department_id' => $serviceType->responsible_department_id,
                'assigned_by' => $actor->getKey(),
                'assigned_at' => now(),
                'note' => $note,
            ]);

            $locked->assigned_staff_id = $lockedStaff->getKey();
            $locked->save();

            $this->activityLogger->recordAssignment($locked, $actor, $lockedStaff, $note);

            return $locked->refresh();
        });
    }

    private function closeActiveAssignments(Application $application): void
    {
        ApplicationAssignment::query()
            ->where('application_id', $application->getKey())
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);
    }
}
