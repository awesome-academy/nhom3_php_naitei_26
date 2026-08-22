<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Department;
use App\Models\User;
use App\Support\Application\ApplicationActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_can_view_activity_log_and_citizen_cannot(): void
    {
        $staff = User::factory()->staff()->create();
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();
        $inactiveManager = User::factory()->manager()->inactive()->create();

        $this->get(route('admin.activity-logs.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($citizen)->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($inactiveManager)->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.activity-logs.index'))->assertOk();
    }

    public function test_activity_log_can_filter_by_actor_action_subject_keyword_and_date(): void
    {
        $actor = User::factory()->staff()->create(['name' => 'Nguyen Audit']);
        $otherActor = User::factory()->manager()->create();
        $application = Application::factory()->create(['application_code' => 'HS-SEARCH-0001']);
        $department = Department::factory()->create(['name' => 'Phong khac']);

        $matchingLog = ActivityLog::query()->create([
            'actor_id' => $actor->id,
            'action' => ApplicationActivityLogger::APPROVED,
            'subject_type' => $application->getMorphClass(),
            'subject_id' => $application->id,
            'description' => 'Duyệt hồ sơ HS-SEARCH-0001.',
            'metadata' => [
                'application' => [
                    'id' => $application->id,
                    'application_code' => $application->application_code,
                    'status_label' => 'Đã duyệt',
                ],
            ],
            'ip_address' => '127.0.0.1',
        ]);
        $matchingLog->forceFill(['created_at' => now()->subDay()])->save();

        $otherLog = ActivityLog::query()->create([
            'actor_id' => $otherActor->id,
            'action' => 'department.updated',
            'subject_type' => $department->getMorphClass(),
            'subject_id' => $department->id,
            'description' => 'Cập nhật phòng ban khác.',
            'metadata' => ['department' => ['name' => $department->name]],
        ]);
        $otherLog->forceFill(['created_at' => now()->subDays(5)])->save();

        $response = $this->actingAs($actor)->get(route('admin.activity-logs.index', [
            'q' => 'HS-SEARCH',
            'actor_id' => $actor->id,
            'action' => ApplicationActivityLogger::APPROVED,
            'subject_type' => $application->getMorphClass(),
            'date_from' => now()->subDays(2)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertSee('HS-SEARCH-0001')
            ->assertSee('Duyệt hồ sơ')
            ->assertSee('Nguyen Audit')
            ->assertDontSee('Cập nhật phòng ban khác')
            ->assertDontSee('Phong khac');
    }

    public function test_invalid_activity_log_filters_show_validation_errors(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->get(route('admin.activity-logs.index', [
            'actor_id' => 'abc',
            'date_from' => '2026-08-20',
            'date_to' => '2026-08-19',
        ]));

        $response->assertSessionHasErrors(['actor_id', 'date_to']);
    }
}
