<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_vietnamese_timeline_labels_and_descriptions(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);

        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $application = Application::query()->create([
            'application_code' => 'HS-20260821-00001',
            'citizen_id' => $citizen->id,
            'service_type_id' => ServiceType::factory()->create()->id,
            'status' => ApplicationStatus::SupplementRequired,
            'form_data' => ['full_name' => 'Nguyen Van A'],
            'submitted_at' => now(),
        ]);

        $application->statusHistories()->create([
            'from_status' => null,
            'to_status' => ApplicationStatus::Received,
            'changed_by' => $citizen->id,
            'created_at' => now()->subHours(2),
        ]);

        $application->statusHistories()->create([
            'from_status' => ApplicationStatus::Processing,
            'to_status' => ApplicationStatus::SupplementRequired,
            'changed_by' => $staff->id,
            'note' => 'Cần bổ sung bản sao CCCD.',
            'created_at' => now()->subHour(),
        ]);

        $this->actingAs($citizen, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('data.timeline.0.label', 'Đã tiếp nhận')
            ->assertJsonPath('data.timeline.0.description', 'Hồ sơ được nộp thành công và bắt đầu quy trình xử lý.')
            ->assertJsonPath('data.timeline.1.from_status_label', 'Đang xử lý')
            ->assertJsonPath('data.timeline.1.to_status_label', 'Cần bổ sung')
            ->assertJsonPath('data.timeline.1.label', 'Cần bổ sung')
            ->assertJsonPath('data.timeline.1.description', 'Cán bộ yêu cầu bổ sung để tiếp tục xử lý hồ sơ.')
            ->assertJsonPath('data.timeline.1.changed_by_name', $staff->name)
            ->assertJsonPath('data.timeline.1.note', 'Cần bổ sung bản sao CCCD.');
    }

    public function test_other_citizen_cannot_view_application_timeline(): void
    {
        $owner = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
        $other = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);

        $application = Application::query()->create([
            'application_code' => 'HS-20260821-00002',
            'citizen_id' => $owner->id,
            'service_type_id' => ServiceType::factory()->create()->id,
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => 'Nguyen Van B'],
            'submitted_at' => now(),
        ]);

        $application->statusHistories()->create([
            'from_status' => null,
            'to_status' => ApplicationStatus::Received,
            'changed_by' => $owner->id,
        ]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/applications/{$application->id}")
            ->assertForbidden();
    }
}
