<?php

namespace Tests\Feature\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $department = Department::factory()->create();

        $this->get(route('admin.departments.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.departments.create'))->assertRedirect(route('admin.login'));
        $this->patch(route('admin.departments.update', $department), $this->updatePayload($department))
            ->assertRedirect(route('admin.login'));
    }

    public function test_citizen_and_inactive_internal_users_are_denied_by_internal_boundary(): void
    {
        $department = Department::factory()->create();
        $actors = [
            User::factory()->withRole(UserRole::Citizen)->create(),
            User::factory()->staff()->inactive()->create(),
        ];

        foreach ($actors as $actor) {
            $this->actingAs($actor)->get(route('admin.departments.index'))->assertForbidden();
            $this->actingAs($actor)
                ->patch(route('admin.departments.update', $department), $this->updatePayload($department))
                ->assertForbidden();
        }
    }

    public function test_staff_cannot_access_department_management(): void
    {
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->create();

        $this->actingAs($staff)->get(route('admin.departments.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.departments.create'))->assertForbidden();
        $this->actingAs($staff)
            ->patch(route('admin.departments.update', $department), $this->updatePayload($department))
            ->assertNotFound();

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => $department->name,
        ]);
    }

    public function test_manager_can_view_only_led_departments_but_cannot_create_or_update(): void
    {
        $manager = User::factory()->manager()->create();
        $ledDepartment = Department::factory()->ledBy($manager)->create();
        $otherDepartment = Department::factory()->create();

        $this->actingAs($manager)->get(route('admin.departments.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.departments.show', $ledDepartment))->assertOk();
        $this->actingAs($manager)->get(route('admin.departments.show', $otherDepartment))->assertNotFound();
        $this->actingAs($manager)->get(route('admin.departments.create'))->assertForbidden();
        $this->actingAs($manager)
            ->patch(route('admin.departments.update', $ledDepartment), $this->updatePayload($ledDepartment))
            ->assertForbidden();
        $this->actingAs($manager)
            ->patch(route('admin.departments.update', $otherDepartment), $this->updatePayload($otherDepartment))
            ->assertNotFound();
    }

    public function test_super_admin_can_create_and_update_departments(): void
    {
        $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.departments.store'), [
                'name' => 'Phòng Tiếp nhận',
                'code' => 'ptn-01',
                'address' => 'Tầng 1',
            ])
            ->assertRedirect();

        $department = Department::query()->where('code', 'PTN-01')->firstOrFail();

        $this->actingAs($superAdmin)
            ->patch(route('admin.departments.update', $department), $this->updatePayload($department))
            ->assertRedirect(route('admin.departments.show', $department));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Phòng ban đã cập nhật',
        ]);
    }

    public function test_collection_and_detail_actions_are_rendered_from_policy_permissions(): void
    {
        $superAdmin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $manager = User::factory()->manager()->create();
        $ledDepartment = Department::factory()->ledBy($manager)->create();
        $outsideDepartment = Department::factory()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee('Tạo phòng ban')
            ->assertSee('Sửa')
            ->assertSee('Lưu trữ');

        $this->actingAs($superAdmin)
            ->get(route('admin.departments.show', $ledDepartment))
            ->assertOk()
            ->assertSee('Sửa')
            ->assertSee('Lưu trữ');

        $this->actingAs($manager)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSee($ledDepartment->name)
            ->assertDontSee($outsideDepartment->name)
            ->assertDontSee('Tạo phòng ban')
            ->assertDontSee('Sửa')
            ->assertDontSee('Lưu trữ');

        $this->actingAs($manager)
            ->get(route('admin.departments.show', $ledDepartment))
            ->assertOk()
            ->assertSee('Thêm thành viên')
            ->assertDontSee('Đổi lãnh đạo')
            ->assertDontSee('Sửa')
            ->assertDontSee('Lưu trữ');

        $this->actingAs($manager)
            ->get(route('admin.departments.show', $outsideDepartment))
            ->assertNotFound();
    }

    public function test_staff_collection_is_forbidden_and_resource_ids_are_masked(): void
    {
        $staff = User::factory()->staff()->create();
        $department = Department::factory()->create();

        $this->actingAs($staff)->get(route('admin.departments.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.departments.show', $department))->assertNotFound();
        $this->actingAs($staff)
            ->get(route('admin.departments.member-candidates', [$department, 'search' => 'staff']))
            ->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(Department $department): array
    {
        return [
            'name' => 'Phòng ban đã cập nhật',
            'code' => $department->code,
            'address' => $department->address,
            'version' => $department->lock_version,
        ];
    }
}
