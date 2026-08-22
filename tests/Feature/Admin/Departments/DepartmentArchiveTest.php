<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationAssignment;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_soft_archives_department_and_preserves_all_relationships_and_history(): void
    {
        $actor = $this->superAdmin();
        $leader = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->ledBy($leader)->create(['code' => 'ARCHIVE-SAFE']);
        $department->users()->attach($staff);
        $service = ServiceType::factory()->create(['responsible_department_id' => $department->id]);
        $assignment = $this->applicationAssignment($department, $service, $staff, $actor);

        $this->actingAs($actor)->delete(route('admin.departments.destroy', $department), [
            'confirmation' => 'archive',
            'version' => 0,
        ])->assertRedirect(route('admin.departments.index'));

        $archived = Department::onlyTrashed()->findOrFail($department->id);
        $this->assertSame(1, $archived->lock_version);
        $this->assertSame($leader->id, $archived->leader_id);
        $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $leader->id]);
        $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $staff->id]);
        $this->assertDatabaseHas('service_types', ['id' => $service->id, 'responsible_department_id' => $department->id]);
        $this->assertDatabaseHas('application_assignments', ['id' => $assignment->id, 'department_id' => $department->id]);
        $this->assertDatabaseHas('users', ['id' => $staff->id]);

        $this->actingAs($actor)->get(route('admin.departments.index'))
            ->assertOk()
            ->assertDontSee('ARCHIVE-SAFE');
        $this->actingAs($actor)->get(route('admin.departments.index', ['status' => 'archived']))
            ->assertOk()
            ->assertSee('ARCHIVE-SAFE');

        $this->actingAs($actor)->post(route('admin.departments.store'), [
            'name' => 'Phòng dùng lại mã',
            'code' => 'archive-safe',
            'address' => null,
        ])->assertSessionHasErrors('code');
    }

    public function test_archived_department_remains_visible_with_relations_but_has_no_mutation_actions(): void
    {
        $actor = $this->superAdmin();
        $leader = User::factory()->manager()->create(['name' => 'Manager lịch sử']);
        $staff = User::factory()->staff()->create(['name' => 'Staff lịch sử']);
        $department = Department::factory()->ledBy($leader)->archived()->create();
        $department->users()->attach($staff);
        $service = ServiceType::factory()->create([
            'name' => 'Dịch vụ lịch sử',
            'responsible_department_id' => $department->id,
        ]);

        $this->actingAs($actor)->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Đã lưu trữ')
            ->assertSee($leader->name)
            ->assertSee($staff->name)
            ->assertSee($service->name)
            ->assertDontSee('data-dialog-open="archive-department"', false)
            ->assertDontSee('data-dialog-open="change-department-leader"', false)
            ->assertDontSee('data-dialog-open="add-department-member"', false)
            ->assertDontSee('data-dialog-open="transfer-member-', false)
            ->assertDontSee('data-dialog-open="remove-member-', false)
            ->assertDontSee(route('admin.departments.edit', $department));

        $this->actingAs($leader)->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Đã lưu trữ')
            ->assertSee($staff->name)
            ->assertSee($service->name);
    }

    public function test_archived_department_is_excluded_from_transfer_targets_and_rejects_every_mutation(): void
    {
        $actor = $this->superAdmin();
        $leader = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $newStaff = User::factory()->staff()->create();
        $archived = Department::factory()->ledBy($leader)->archived()->create(['name' => 'Mục tiêu lưu trữ']);
        $archived->users()->attach($staff);
        $source = Department::factory()->create();
        $source->users()->attach($newStaff);

        $this->actingAs($actor)->getJson(route(
            'admin.departments.members.transfer-targets',
            [$source, $newStaff, 'search' => 'Mục tiêu'],
        ))->assertOk()->assertJsonMissing(['id' => $archived->id]);

        $this->actingAs($actor)->patch(route('admin.departments.update', $archived), [
            'name' => 'Không được sửa',
            'code' => $archived->code,
            'address' => null,
            'version' => $archived->lock_version,
        ])->assertForbidden();
        $this->actingAs($actor)->patch(route('admin.departments.leader.update', $archived), [
            'leader_id' => null,
            'version' => $archived->lock_version,
        ])->assertForbidden();
        $this->actingAs($actor)->post(route('admin.departments.members.store', $archived), [
            'user_id' => $newStaff->id,
            'version' => $archived->lock_version,
        ])->assertForbidden();
        $this->actingAs($actor)->delete(route('admin.departments.members.destroy', [$archived, $staff]), [
            'version' => $archived->lock_version,
        ])->assertForbidden();
        $this->actingAs($actor)->post(route('admin.departments.members.transfer', [$archived, $staff]), [
            'target_department_id' => $source->id,
            'source_version' => $archived->lock_version,
            'target_version' => $source->lock_version,
        ])->assertForbidden();
        $this->actingAs($actor)->delete(route('admin.departments.destroy', $archived), [
            'confirmation' => 'archive',
            'version' => $archived->lock_version,
        ])->assertForbidden();

        $archived = Department::withTrashed()->findOrFail($archived->id);
        $this->assertSame('Mục tiêu lưu trữ', $archived->name);
        $this->assertTrue($archived->users()->whereKey($staff->id)->exists());
        $this->assertFalse($archived->users()->whereKey($newStaff->id)->exists());
    }

    public function test_archive_requires_super_admin_confirmation_and_current_version(): void
    {
        $manager = User::factory()->manager()->create();
        $department = Department::factory()->ledBy($manager)->create(['lock_version' => 2]);

        $this->actingAs($manager)->delete(route('admin.departments.destroy', $department), [
            'confirmation' => 'archive',
            'version' => 2,
        ])->assertForbidden();

        $actor = $this->superAdmin();
        $this->actingAs($actor)->delete(route('admin.departments.destroy', $department), [
            'confirmation' => 'wrong',
            'version' => 2,
        ])->assertSessionHasErrors('confirmation');
        $this->actingAs($actor)->delete(route('admin.departments.destroy', $department), [
            'confirmation' => 'archive',
            'version' => 1,
        ])->assertConflict();

        $this->assertFalse($department->fresh()->trashed());
        $this->assertSame(2, $department->fresh()->lock_version);
    }

    public function test_super_admin_can_restore_archived_department(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->archived()->create(['code' => 'RESTORE-ME']);

        $response = $this->actingAs($actor)->post(route('admin.departments.restore', $department));

        $response->assertRedirect(route('admin.departments.index'));
        $this->assertFalse($department->fresh()->trashed());
    }

    private function applicationAssignment(
        Department $department,
        ServiceType $service,
        User $staff,
        User $actor,
    ): ApplicationAssignment {
        $application = Application::query()->create([
            'application_code' => 'ARCHIVE-HISTORY-01',
            'citizen_id' => User::factory()->withRole(UserRole::Citizen)->create()->id,
            'service_type_id' => $service->id,
            'assigned_staff_id' => $staff->id,
            'status' => ApplicationStatus::Processing,
            'form_data' => [],
        ]);

        return ApplicationAssignment::query()->create([
            'application_id' => $application->id,
            'staff_id' => $staff->id,
            'department_id' => $department->id,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
