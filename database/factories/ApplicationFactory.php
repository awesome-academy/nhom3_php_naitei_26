<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'application_code' => fake()->unique()->bothify('HS-########-#####'),
            'citizen_id' => User::factory(),
            'service_type_id' => ServiceType::factory(),
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => fake()->name()],
            'submitted_at' => now(),
        ];
    }

    public function withStatus(ApplicationStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'processing_started_at' => in_array($status, [
                ApplicationStatus::Assigned,
                ApplicationStatus::Processing,
                ApplicationStatus::SupplementRequired,
                ApplicationStatus::PendingApproval,
                ApplicationStatus::Approved,
                ApplicationStatus::Rejected,
            ], true) ? now() : null,
            'completed_at' => in_array($status, [
                ApplicationStatus::Approved,
                ApplicationStatus::Rejected,
            ], true) ? now() : null,
            'result_note' => $status === ApplicationStatus::Approved ? fake()->sentence() : null,
            'rejection_reason' => $status === ApplicationStatus::Rejected ? fake()->sentence() : null,
        ]);
    }

    public function assignedTo(User $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_staff_id' => $staff->getKey(),
        ]);
    }
}
