<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\ApplicationStatusHistory;
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

            if ($locked->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Không thể phân công hồ sơ đã hoàn tất.',
                ]);
            }

            // Conditional reason: if already assigned/processing, note is required
            $hasActiveAssignment = ApplicationAssignment::query()
                ->where('application_id', $locked->getKey())
                ->whereNull('ended_at')
                ->exists();
            $requiresReason = $hasActiveAssignment
                || $locked->assigned_staff_id !== null
                || in_array($locked->status, [ApplicationStatus::Processing, ApplicationStatus::SupplementRequired, ApplicationStatus::PendingApproval, ApplicationStatus::Assigned], true);

            if ($requiresReason && blank($note)) {
                throw ValidationException::withMessages([
                    'note' => 'Vui lòng nhập lý do khi đổi người xử lý hồ sơ đã được nhận/đang xử lý.',
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

            if ($locked->assigned_staff_id !== null && (int) $locked->assigned_staff_id === (int) $lockedStaff->getKey()) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Không thể đổi cho chính cán bộ đang xử lý hồ sơ này.',
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

            // Transition to Assigned if currently Received (first assignment)
            if ($locked->status === ApplicationStatus::Received) {
                $from = $locked->status;
                $locked->status = ApplicationStatus::Assigned;
                $locked->save();
                ApplicationStatusHistory::query()->create([
                    'application_id' => $locked->getKey(),
                    'from_status' => $from,
                    'to_status' => ApplicationStatus::Assigned,
                    'changed_by' => $actor->getKey(),
                    'note' => $note,
                ]);
            } else {
                $locked->save();
            }

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
