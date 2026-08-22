<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_can_be_created_with_optional_leader_and_membership_atomically(): void
    {
        $actor = $this->superAdmin();
        $manager = User::factory()->manager()->create();

        $this->actingAs($actor)->post(route('admin.departments.store'), [
            'name' => 'Phòng có lãnh đạo',
            'code' => 'LEADER-01',
            'address' => null,
            'leader_id' => $manager->id,
        ])->assertRedirect();

        $department = Department::query()->where('code', 'LEADER-01')->firstOrFail();
        $this->assertSame($manager->id, $department->leader_id);
        $this->assertTrue($department->users()->whereKey($manager->id)->exists());
    }

    public function test_super_admin_can_set_and_unset_eligible_leader_with_single_membership(): void
    {
        $actor = $this->superAdmin();
        $manager = User::factory()->manager()->create();
        $department = Department::factory()->create();

        $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
            'leader_id' => $manager->id,
            'version' => 0,
        ])->assertRedirect(route('admin.departments.show', $department));

        $department->refresh();
        $this->assertSame($manager->id, $department->leader_id);
        $this->assertSame(1, $department->users()->whereKey($manager->id)->count());

        $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
            'leader_id' => $manager->id,
            'version' => 1,
        ])->assertRedirect();
        $this->assertSame(1, $department->users()->whereKey($manager->id)->count());

        $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
            'leader_id' => null,
            'version' => 1,
        ])->assertRedirect();

        $department->refresh();
        $this->assertNull($department->leader_id);
        $this->assertTrue($department->users()->whereKey($manager->id)->exists());
    }

    public function test_ineligible_users_cannot_be_selected_as_leader(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();
        $candidates = [
            User::factory()->staff()->create(),
            User::factory()->withRole(UserRole::Citizen)->create(),
            User::factory()->withRole(UserRole::SuperAdmin)->create(),
            User::factory()->manager()->inactive()->create(),
            User::factory()->manager()->create(['deleted_at' => now()]),
        ];

        foreach ($candidates as $candidate) {
            $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
                'leader_id' => $candidate->id,
                'version' => 0,
            ])->assertSessionHasErrors('leader_id');
        }

        $this->assertNull($department->fresh()->leader_id);
    }

    public function test_manager_assigned_to_another_department_cannot_be_selected_as_leader(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();
        $assignedManager = User::factory()->manager()->create();
        Department::factory()->create()->users()->attach($assignedManager);

        $this->actingAs($actor)->patch(route('admin.departments.leader.update', $department), [
            'leader_id' => $assignedManager->id,
            'version' => 0,
        ])->assertSessionHasErrors('leader_id');

        $this->assertNull($department->fresh()->leader_id);
    }

    public function test_only_unassigned_staff_can_be_added_as_a_department_member(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();
        $staff = User::factory()->staff()->create();
        $manager = User::factory()->manager()->create();
        $assignedStaff = User::factory()->staff()->create();
        Department::factory()->create()->users()->attach($assignedStaff);

        $this->actingAs($actor)->post(route('admin.departments.members.store', $department), [
            'user_id' => $staff->id,
            'version' => 0,
        ])->assertRedirect(route('admin.departments.show', $department));

        foreach ([$staff, $manager, $assignedStaff] as $ineligibleMember) {
            $this->actingAs($actor)->post(route('admin.departments.members.store', $department), [
                'user_id' => $ineligibleMember->id,
                'version' => 1,
            ])->assertSessionHasErrors('user_id');
        }

        $this->assertSame(1, $department->users()->whereKey($staff->id)->count());
        $this->assertFalse($department->users()->whereKey($manager->id)->exists());
        $this->assertFalse($department->users()->whereKey($assignedStaff->id)->exists());
    }

    public function test_department_creation_rejects_a_manager_already_assigned_elsewhere(): void
    {
        $actor = $this->superAdmin();
        $assignedManager = User::factory()->manager()->create();
        Department::factory()->create()->users()->attach($assignedManager);

        $this->actingAs($actor)->post(route('admin.departments.store'), [
            'name' => 'Phòng không hợp lệ',
            'code' => 'INVALID-LEADER',
            'address' => null,
            'leader_id' => $assignedManager->id,
        ])->assertSessionHasErrors('leader_id');

        $this->assertDatabaseMissing('departments', ['code' => 'INVALID-LEADER']);
    }

    public function test_manager_can_manage_only_staff_in_led_department(): void
    {
        $manager = User::factory()->manager()->create();
        $ledDepartment = Department::factory()->ledBy($manager)->create();
        $otherDepartment = Department::factory()->create();
        $staff = User::factory()->staff()->create();
        $managerMember = User::factory()->manager()->create();

        $this->actingAs($manager)->post(route('admin.departments.members.store', $ledDepartment), [
            'user_id' => $staff->id,
            'version' => 0,
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('admin.departments.members.store', $ledDepartment), [
            'user_id' => $managerMember->id,
            'version' => 1,
        ])->assertSessionHasErrors('user_id');

        $this->actingAs($manager)->post(route('admin.departments.members.store', $otherDepartment), [
            'user_id' => $staff->id,
            'version' => 0,
        ])->assertNotFound();

        $this->assertTrue($ledDepartment->users()->whereKey($staff->id)->exists());
        $this->assertFalse($ledDepartment->users()->whereKey($managerMember->id)->exists());
        $this->assertFalse($otherDepartment->users()->whereKey($staff->id)->exists());
    }

    public function test_current_leader_cannot_be_removed_but_staff_can_be_removed_without_deleting_user(): void
    {
        $actor = $this->superAdmin();
        $leader = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->ledBy($leader)->create();
        $department->users()->attach($staff);

        $this->actingAs($actor)->delete(route('admin.departments.members.destroy', [$department, $leader]), [
            'version' => 0,
        ])->assertSessionHasErrors('member');

        $this->actingAs($actor)->delete(route('admin.departments.members.destroy', [$department, $staff]), [
            'version' => 0,
        ])->assertRedirect(route('admin.departments.show', $department));

        $this->assertTrue($department->users()->whereKey($leader->id)->exists());
        $this->assertFalse($department->users()->whereKey($staff->id)->exists());
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
