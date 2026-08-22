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

final readonly class ClaimApplicationAction
{
    public function __construct(
        private ApplicationActivityLogger $activityLogger,
    ) {}

    public function handle(Application $application, User $actor): Application
    {
        return DB::transaction(function () use ($application, $actor): Application {
            $lockedActor = User::query()->lockForUpdate()->find($actor->getKey());

            if (
                $lockedActor === null
                || (! $lockedActor->isStaff() && ! $lockedActor->isSuperAdmin())
                || ! $lockedActor->canAccessProtectedResources()
            ) {
                throw ValidationException::withMessages([
                    'application' => 'Chỉ Staff đang hoạt động hoặc Super Admin mới có thể nhận hồ sơ.',
                ]);
            }

            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            Gate::forUser($lockedActor)->authorize('claim', $locked);

            if ($locked->assigned_staff_id !== null || $locked->status !== ApplicationStatus::Received) {
                throw ValidationException::withMessages([
                    'application' => 'Hồ sơ này đã có người phụ trách hoặc không thể nhận.',
                ]);
            }

            $serviceType = $locked->serviceType;

            if (! $lockedActor->isSuperAdmin() && ($serviceType === null || ! $lockedActor->departments()->whereKey($serviceType->responsible_department_id)->exists())) {
                throw ValidationException::withMessages([
                    'application' => 'Bạn không thuộc phòng ban phụ trách dịch vụ của hồ sơ này.',
                ]);
            }

            ApplicationAssignment::query()->create([
                'application_id' => $locked->getKey(),
                'staff_id' => $lockedActor->getKey(),
                'department_id' => $serviceType->responsible_department_id,
                'assigned_by' => $lockedActor->getKey(),
                'assigned_at' => now(),
            ]);

            $locked->assigned_staff_id = $lockedActor->getKey();
            $locked->save();

            $this->activityLogger->recordClaim($locked, $lockedActor);

            return $locked->refresh();
        });
    }
}
