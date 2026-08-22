<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepartmentTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_transfers_staff_atomically_and_preserves_business_history(): void
    {
        $actor = $this->superAdmin();
        $staff = User::factory()->staff()->create();
        $target = Department::factory()->create(['code' => 'TARGET-FIRST']);
        $source = Department::factory()->create(['code' => 'SOURCE-SECOND']);
        $source->users()->attach($staff);
        $assignment = $this->applicationAssignment($source, $staff, $actor);
        $lockQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$lockQueries): void {
            if (str_contains($query->sql, 'departments') && str_contains($query->sql, 'for update')) {
                $lockQueries[] = $query->sql;
            }
        });

        $this->actingAs($actor)->post(route('admin.departments.members.transfer', [$source, $staff]), [
            'target_department_id' => $target->id,
            'source_version' => 0,
            'target_version' => 0,
        ])->assertRedirect(route('admin.departments.show', $target));

        $this->assertFalse($source->users()->whereKey($staff->id)->exists());
        $this->assertSame(1, $target->users()->whereKey($staff->id)->count());
        $this->assertSame(1, $source->fresh()->lock_version);
        $this->assertSame(1, $target->fresh()->lock_version);
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('application_assignments', [
            'id' => $assignment->id,
            'staff_id' => $staff->id,
            'department_id' => $source->id,
        ]);
        $this->assertTrue(collect($lockQueries)->contains(
            fn (string $sql): bool => str_contains($sql, 'order by "id" asc'),
        ));
    }

    public function test_invalid_duplicate_archived_and_stale_targets_leave_both_sides_unchanged(): void
    {
        $actor = $this->superAdmin();
        $staff = User::factory()->staff()->create();
        $source = Department::factory()->create();
        $target = Department::factory()->create();
        $duplicateTarget = Department::factory()->create();
        $archivedTarget = Department::factory()->archived()->create();
        $source->users()->attach($staff);
        $duplicateTarget->users()->attach($staff);

        $payloads = [
            ['target_department_id' => $source->id, 'source_version' => 0, 'target_version' => 0],
            ['target_department_id' => $duplicateTarget->id, 'source_version' => 0, 'target_version' => 0],
            ['target_department_id' => $archivedTarget->id, 'source_version' => 0, 'target_version' => 0],
            ['target_department_id' => 999999, 'source_version' => 0, 'target_version' => 0],
        ];

        foreach ($payloads as $payload) {
            $this->actingAs($actor)
                ->post(route('admin.departments.members.transfer', [$source, $staff]), $payload)
                ->assertSessionHasErrors('target_department_id');
        }

        $this->actingAs($actor)
            ->post(route('admin.departments.members.transfer', [$source, $staff]), [
                'target_department_id' => $target->id,
                'source_version' => 0,
                'target_version' => 99,
            ])
            ->assertConflict();

        $this->assertTrue($source->users()->whereKey($staff->id)->exists());
        $this->assertFalse($target->users()->whereKey($staff->id)->exists());
        $this->assertTrue($duplicateTarget->users()->whereKey($staff->id)->exists());
        $this->assertSame(0, $source->fresh()->lock_version);
        $this->assertSame(0, $target->fresh()->lock_version);
    }

    public function test_only_active_staff_with_a_source_membership_can_be_transferred(): void
    {
        $actor = $this->superAdmin();
        $source = Department::factory()->create();
        $target = Department::factory()->create();
        $manager = User::factory()->manager()->create();
        $inactiveStaff = User::factory()->staff()->inactive()->create();
        $unrelatedStaff = User::factory()->staff()->create();
        $source->users()->attach([$manager->id, $inactiveStaff->id]);

        foreach ([$manager, $inactiveStaff] as $member) {
            $this->actingAs($actor)
                ->post(route('admin.departments.members.transfer', [$source, $member]), [
                    'target_department_id' => $target->id,
                    'source_version' => 0,
                    'target_version' => 0,
                ])
                ->assertSessionHasErrors('member');
        }

        $this->actingAs($actor)
            ->post(route('admin.departments.members.transfer', [$source, $unrelatedStaff]), [
                'target_department_id' => $target->id,
                'source_version' => 0,
                'target_version' => 0,
            ])
            ->assertNotFound();
    }

    public function test_legacy_staff_with_another_active_membership_cannot_be_assigned_to_a_new_department(): void
    {
        $actor = $this->superAdmin();
        $staff = User::factory()->staff()->create();
        $source = Department::factory()->create();
        $legacyDepartment = Department::factory()->create();
        $target = Department::factory()->create();
        $source->users()->attach($staff);
        $legacyDepartment->users()->attach($staff);

        $this->actingAs($actor)
            ->post(route('admin.departments.members.transfer', [$source, $staff]), [
                'target_department_id' => $target->id,
                'source_version' => 0,
                'target_version' => 0,
            ])
            ->assertSessionHasErrors('member');

        $this->assertTrue($source->users()->whereKey($staff->id)->exists());
        $this->assertTrue($legacyDepartment->users()->whereKey($staff->id)->exists());
        $this->assertFalse($target->users()->whereKey($staff->id)->exists());
    }

    public function test_manager_must_lead_both_source_and_target(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $source = Department::factory()->ledBy($manager)->create();
        $allowedTarget = Department::factory()->ledBy($manager)->create();
        $forbiddenTarget = Department::factory()->ledBy($otherManager)->create();
        $source->users()->attach($staff);

        $this->actingAs($manager)
            ->post(route('admin.departments.members.transfer', [$source, $staff]), [
                'target_department_id' => $forbiddenTarget->id,
                'source_version' => 0,
                'target_version' => 0,
            ])
            ->assertSessionHasErrors('target_department_id');

        $this->actingAs($manager)
            ->post(route('admin.departments.members.transfer', [$source, $staff]), [
                'target_department_id' => $allowedTarget->id,
                'source_version' => 0,
                'target_version' => 0,
            ])
            ->assertRedirect(route('admin.departments.show', $allowedTarget));

        $this->assertFalse($source->users()->whereKey($staff->id)->exists());
        $this->assertTrue($allowedTarget->users()->whereKey($staff->id)->exists());
        $this->assertFalse($forbiddenTarget->users()->whereKey($staff->id)->exists());
    }

    public function test_staff_and_manager_outside_source_scope_receive_masked_not_found(): void
    {
        $manager = User::factory()->manager()->create();
        $staffActor = User::factory()->staff()->create();
        $member = User::factory()->staff()->create();
        $source = Department::factory()->create();
        $target = Department::factory()->create();
        $source->users()->attach($member);
        $payload = [
            'target_department_id' => $target->id,
            'source_version' => 0,
            'target_version' => 0,
        ];

        $this->actingAs($manager)
            ->post(route('admin.departments.members.transfer', [$source, $member]), $payload)
            ->assertNotFound();
        $this->actingAs($staffActor)
            ->post(route('admin.departments.members.transfer', [$source, $member]), $payload)
            ->assertNotFound();

        $this->assertTrue($source->users()->whereKey($member->id)->exists());
        $this->assertFalse($target->users()->whereKey($member->id)->exists());
    }

    public function test_transfer_target_candidates_are_bounded_and_authorization_scoped(): void
    {
        $manager = User::factory()->manager()->create();
        $otherManager = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $source = Department::factory()->ledBy($manager)->create(['name' => 'Nguồn chuyển']);
        $source->users()->attach($staff);
        $duplicate = Department::factory()->ledBy($manager)->create(['name' => 'Đích đã có người']);
        $duplicate->users()->attach($staff);
        Department::factory()->ledBy($manager)->archived()->create(['name' => 'Đích lưu trữ']);
        Department::factory()->ledBy($otherManager)->create(['name' => 'Đích ngoài phạm vi']);
        Department::factory()->ledBy($manager)->count(22)->create(['name' => 'Đích hợp lệ']);

        $response = $this->actingAs($manager)->getJson(route(
            'admin.departments.members.transfer-targets',
            [$source, $staff, 'search' => 'Đích'],
        ));

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.has_more', true);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($source->id, $ids);
        $this->assertNotContains($duplicate->id, $ids);
        $this->assertSame(
            ['id', 'name', 'code', 'version'],
            array_keys($response->json('data.0')),
        );
    }

    private function applicationAssignment(Department $source, User $staff, User $actor): ApplicationAssignment
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();
        $service = ServiceType::factory()->create(['responsible_department_id' => $source->id]);
        $application = Application::query()->create([
            'application_code' => 'TRANSFER-HISTORY-01',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
            'assigned_staff_id' => $staff->id,
            'status' => ApplicationStatus::Processing,
            'form_data' => [],
        ]);

        return ApplicationAssignment::query()->create([
            'application_id' => $application->id,
            'staff_id' => $staff->id,
            'department_id' => $source->id,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
