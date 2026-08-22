<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\ApplicationCodeService;
use App\Support\Application\ApplicationWorkflowNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateApplicationAction
{
    public function __construct(
        private readonly ApplicationCodeService $applicationCodeService,
        private readonly ApplicationWorkflowNotifier $workflowNotifier,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     */
    public function execute(User $citizen, ServiceType $serviceType, array $formData): Application
    {
        return DB::transaction(function () use ($citizen, $serviceType, $formData): Application {
            $now = CarbonImmutable::now();
            $applicationCode = $this->applicationCodeService->generateForDate($now);

            $application = Application::query()->create([
                'application_code' => $applicationCode,
                'citizen_id' => $citizen->id,
                'service_type_id' => $serviceType->id,
                'status' => ApplicationStatus::Received,
                'form_data' => $formData,
                'submitted_at' => $now,
            ]);

            ApplicationStatusHistory::query()->create([
                'application_id' => $application->id,
                'from_status' => null,
                'to_status' => ApplicationStatus::Received,
                'changed_by' => $citizen->id,
            ]);

            $this->workflowNotifier->applicationSubmitted($application, $citizen);

            return $application;
        });
    }
}
