<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentCandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_candidate_search_is_bounded_and_filters_role_and_status(): void
    {
        $actor = $this->superAdmin();
        User::factory()->count(22)->manager()->sequence(
            fn ($sequence) => ['name' => sprintf('Candidate Manager %02d', $sequence->index)],
        )->create();
        User::factory()->staff()->create(['name' => 'Candidate Staff']);
        $assignedManager = User::factory()->manager()->create(['name' => 'Candidate Assigned']);
        Department::factory()->create()->users()->attach($assignedManager);
        User::factory()->manager()->inactive()->create(['name' => 'Candidate Inactive']);
        User::factory()->manager()->create(['name' => 'Candidate Deleted', 'deleted_at' => now()]);

        $this->actingAs($actor)
            ->getJson(route('admin.departments.manager-candidates', ['search' => 'Candidate']))
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonMissing(['name' => 'Candidate Staff'])
            ->assertJsonMissing(['name' => 'Candidate Assigned'])
            ->assertJsonMissing(['name' => 'Candidate Inactive'])
            ->assertJsonMissing(['name' => 'Candidate Deleted']);

        $this->actingAs($actor)
            ->getJson(route('admin.departments.manager-candidates', ['search' => 'C']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_member_candidates_only_return_unassigned_active_staff(): void
    {
        $superAdmin = $this->superAdmin();
        $manager = User::factory()->manager()->create(['name' => 'Scope Manager']);
        $department = Department::factory()->ledBy($manager)->create();
        $staff = User::factory()->staff()->create(['name' => 'Scope Staff']);
        $managerCandidate = User::factory()->manager()->create(['name' => 'Scope Candidate Manager']);
        $existingStaff = User::factory()->staff()->create(['name' => 'Scope Existing Staff']);
        $assignedElsewhere = User::factory()->staff()->create(['name' => 'Scope Assigned Elsewhere']);
        $inactiveStaff = User::factory()->staff()->inactive()->create(['name' => 'Scope Inactive Staff']);
        $department->users()->attach($existingStaff);
        Department::factory()->create()->users()->attach($assignedElsewhere);

        $this->actingAs($superAdmin)
            ->getJson(route('admin.departments.member-candidates', [$department, 'search' => 'Scope']))
            ->assertOk()
            ->assertJsonFragment(['id' => $staff->id])
            ->assertJsonMissing(['id' => $managerCandidate->id])
            ->assertJsonMissing(['id' => $existingStaff->id])
            ->assertJsonMissing(['id' => $assignedElsewhere->id])
            ->assertJsonMissing(['id' => $inactiveStaff->id]);

        $this->actingAs($manager)
            ->getJson(route('admin.departments.member-candidates', [$department, 'search' => 'Scope']))
            ->assertOk()
            ->assertJsonFragment(['id' => $staff->id])
            ->assertJsonMissing(['id' => $managerCandidate->id]);
    }

    public function test_staff_dropdown_only_lists_unassigned_active_staff(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();
        $availableStaff = User::factory()->staff()->create(['name' => 'Nhân viên phù hợp']);
        $assignedStaff = User::factory()->staff()->create(['name' => 'Nhân viên đã phân công']);
        User::factory()->manager()->create(['name' => 'Quản lý không thuộc danh sách']);
        $inactiveStaff = User::factory()->staff()->inactive()->create(['name' => 'Nhân viên ngừng hoạt động']);
        Department::factory()->create()->users()->attach($assignedStaff);

        $this->actingAs($actor)
            ->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertViewHas('staffCandidates', fn ($candidates): bool => $candidates->pluck('id')->all() === [$availableStaff->id])
            ->assertSee('department-staff-select')
            ->assertSee($availableStaff->name)
            ->assertDontSee($assignedStaff->name)
            ->assertDontSee($inactiveStaff->name);
    }

    public function test_staff_dropdown_shows_an_empty_state_when_no_staff_is_available(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();

        $this->actingAs($actor)
            ->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Đang chưa có staff nào phù hợp.');
    }

    public function test_leader_dropdown_only_lists_unassigned_active_managers(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();
        $availableManager = User::factory()->manager()->create(['name' => 'Quản lý phù hợp']);
        $assignedMember = User::factory()->manager()->create(['name' => 'Quản lý đã là thành viên']);
        $assignedLeader = User::factory()->manager()->create(['name' => 'Quản lý đang lãnh đạo']);
        $inactiveManager = User::factory()->manager()->inactive()->create(['name' => 'Quản lý ngừng hoạt động']);
        Department::factory()->create()->users()->attach($assignedMember);
        Department::factory()->ledBy($assignedLeader)->create();

        $this->actingAs($actor)
            ->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertViewHas('leaderCandidates', fn ($candidates): bool => $candidates->pluck('id')->all() === [$availableManager->id])
            ->assertSee('department-leader-select')
            ->assertSee($availableManager->name)
            ->assertDontSee($assignedMember->name)
            ->assertDontSee($assignedLeader->name)
            ->assertDontSee($inactiveManager->name);
    }

    public function test_leader_dropdown_shows_an_empty_state_when_no_manager_is_available(): void
    {
        $actor = $this->superAdmin();
        $department = Department::factory()->create();

        $this->actingAs($actor)
            ->get(route('admin.departments.show', $department))
            ->assertOk()
            ->assertSee('Đang chưa có lãnh đạo nào phù hợp.');
    }

    public function test_candidate_routes_do_not_enumerate_out_of_scope_departments(): void
    {
        $manager = User::factory()->manager()->create();
        $otherDepartment = Department::factory()->create();

        $this->actingAs($manager)
            ->getJson(route('admin.departments.member-candidates', [
                $otherDepartment,
                'search' => 'Staff',
            ]))
            ->assertNotFound();

        $this->actingAs(User::factory()->withRole(UserRole::Staff)->create())
            ->getJson(route('admin.departments.manager-candidates', ['search' => 'Manager']))
            ->assertForbidden();
    }

    private function superAdmin(): User
    {
        return User::factory()->withRole(UserRole::SuperAdmin)->create();
    }
}
