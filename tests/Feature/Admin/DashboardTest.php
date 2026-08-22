<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_six_metrics_follow_staff_manager_and_super_admin_visibility(): void
    {
        $manager = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $outsideStaff = User::factory()->staff()->create();
        $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $department = Department::factory()->ledBy($manager)->create();
        $outsideDepartment = Department::factory()->create();
        $department->users()->attach($staff);
        $outsideDepartment->users()->attach($outsideStaff);

        $service = ServiceType::factory()->create([
            'responsible_department_id' => $department->id,
            'processing_time_days' => 2,
        ]);
        $outsideService = ServiceType::factory()->create([
            'responsible_department_id' => $outsideDepartment->id,
            'processing_time_days' => 2,
        ]);

        $this->makeApplication($service, $staff, ApplicationStatus::Received, 0);
        $this->makeApplication($service, $staff, ApplicationStatus::Processing, 5);
        $this->makeApplication($service, $staff, ApplicationStatus::SupplementRequired, 4);
        $this->makeApplication($service, $staff, ApplicationStatus::Approved, 10);
        $this->makeApplication($service, $outsideStaff, ApplicationStatus::Rejected, 10);
        $this->makeApplication($outsideService, $outsideStaff, ApplicationStatus::Received, 5);

        $this->assertMetricsFor($staff, [
            'total' => 4,
            'received' => 1,
            'processing' => 1,
            'supplement_required' => 1,
            'completed' => 1,
            'overdue' => 2,
        ]);
        $this->assertMetricsFor($manager, [
            'total' => 5,
            'received' => 1,
            'processing' => 1,
            'supplement_required' => 1,
            'completed' => 2,
            'overdue' => 2,
        ]);
        $this->assertMetricsFor($superAdmin, [
            'total' => 6,
            'received' => 2,
            'processing' => 1,
            'supplement_required' => 1,
            'completed' => 2,
            'overdue' => 3,
        ]);
    }

    public function test_dashboard_renders_zero_values_for_an_empty_authorized_scope(): void
    {
        $staff = User::factory()->staff()->create();
        Application::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', [
                'total' => 0,
                'received' => 0,
                'processing' => 0,
                'supplement_required' => 0,
                'completed' => 0,
                'overdue' => 0,
            ])
            ->assertSee('Tổng quan vận hành')
            ->assertSee('Tổng hồ sơ')
            ->assertSee('0');
    }

    public function test_admin_header_does_not_duplicate_the_application_workspace_link(): void
    {
        $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $matched = preg_match(
            '/<nav[^>]*aria-label="Điều hướng quản trị"[^>]*>(.*?)<\/nav>/s',
            $response->getContent(),
            $navigation,
        );

        $this->assertSame(1, $matched);
        $this->assertStringNotContainsString('Hồ sơ', $navigation[1]);
    }

    public function test_every_metric_drill_down_has_the_same_authorized_total(): void
    {
        $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $service = ServiceType::factory()->create(['processing_time_days' => 1]);

        foreach (ApplicationStatus::cases() as $index => $status) {
            $this->makeApplication(
                $service,
                null,
                $status,
                $index === 0 ? 4 : 0,
            );
        }

        $dashboard = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $dashboard->assertOk();

        /** @var array<string, int> $metrics */
        $metrics = $dashboard->viewData('metrics');
        /** @var array<string, array{url: string}> $metricCards */
        $metricCards = $dashboard->viewData('metricCards');

        foreach ($metricCards as $key => $card) {
            $this->actingAs($superAdmin)
                ->get($card['url'])
                ->assertOk()
                ->assertViewHas(
                    'applications',
                    fn ($applications): bool => $applications->total() === $metrics[$key],
                );
        }
    }

    public function test_dashboard_uses_a_bounded_number_of_queries_and_get_does_not_mutate_data(): void
    {
        $manager = User::factory()->manager()->create();
        $department = Department::factory()->ledBy($manager)->create();
        $service = ServiceType::factory()->create([
            'responsible_department_id' => $department->id,
            'processing_time_days' => 3,
        ]);
        $application = Application::factory()->create([
            'service_type_id' => $service->id,
            'status' => ApplicationStatus::Processing,
            'submitted_at' => now()->subDays(4),
        ]);
        $before = $application->fresh()->getAttributes();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(4, $queryCount, "Dashboard executed {$queryCount} queries.");
        $this->assertSame($before, $application->fresh()->getAttributes());
        $this->assertDatabaseCount('applications', 1);
    }

    /** @param array<string, int> $expected */
    private function assertMetricsFor(User $actor, array $expected): void
    {
        $this->actingAs($actor)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('metrics', $expected)
            ->assertViewHas('metricCards', fn (array $cards): bool => array_keys($cards) === array_keys($expected));
    }

    private function makeApplication(
        ServiceType $service,
        ?User $staff,
        ApplicationStatus $status,
        int $submittedDaysAgo,
    ): Application {
        return Application::factory()
            ->withStatus($status)
            ->create([
                'service_type_id' => $service->id,
                'assigned_staff_id' => $staff?->id,
                'submitted_at' => now()->subDays($submittedDaysAgo),
            ]);
    }
}
