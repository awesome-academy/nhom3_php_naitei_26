<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_cannot_export_data(): void
    {
        $response = $this->get('/admin/export/citizens');

        $response->assertRedirect('/admin/login');
    }

    public function test_citizen_cannot_export_data(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $response = $this->actingAs($citizen)->get('/admin/export/citizens');

        $response->assertForbidden();
    }

    public function test_staff_cannot_export_data(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $response = $this->actingAs($staff)->get('/admin/export/citizens');

        $response->assertForbidden();
    }

    public function test_manager_cannot_export_data(): void
    {
        $manager = User::factory()->withRole(UserRole::Manager)->create();

        $response = $this->actingAs($manager)->get('/admin/export/citizens');

        $response->assertForbidden();
    }

    public function test_admin_can_export_citizens_csv(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Nguyễn Văn Citizen TestExport',
            'email' => 'citizentestexport@example.com',
            'citizen_id' => '001099887766',
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/citizens');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="citizens-export-', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Nguyễn Văn Citizen TestExport', $content);
        $this->assertStringContainsString('citizentestexport@example.com', $content);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.export.citizens',
        ]);
    }

    public function test_admin_can_export_staff_csv(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $staff = User::factory()->withRole(UserRole::Staff)->create([
            'name' => 'Trần Văn Staff ExportTest',
            'email' => 'staffexporttest@example.com',
        ]);

        $department = Department::factory()->create(['name' => 'Phòng CNTT TestExport']);
        $staff->departments()->attach($department->id);

        $response = $this->actingAs($admin)->get('/admin/export/staff');

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Trần Văn Staff ExportTest', $content);
        $this->assertStringContainsString('staffexporttest@example.com', $content);
        $this->assertStringContainsString('Phòng CNTT TestExport', $content);
    }

    public function test_admin_can_export_applications_csv(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $citizen = User::factory()->withRole(UserRole::Citizen)->create(['name' => 'Nguyễn Thị AppUser']);
        $service = ServiceType::factory()->create(['name' => 'Cấp lại CCCD Hỏng']);

        $application = Application::factory()->create([
            'application_code' => 'HS-TESTEXPORT-999',
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/applications');

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('HS-TESTEXPORT-999', $content);
        $this->assertStringContainsString('Nguyễn Thị AppUser', $content);
        $this->assertStringContainsString('Cấp lại CCCD Hỏng', $content);
    }

    public function test_admin_can_export_services_csv(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $category = ServiceCategory::factory()->create(['name' => 'Hộ tịch & Nhân thân']);
        $department = Department::factory()->create(['name' => 'Phòng Tư pháp']);

        ServiceType::factory()->create([
            'code' => 'DV-TESTEXPORT-01',
            'name' => 'Đăng ký kết hôn trực tuyến',
            'category_id' => $category->id,
            'responsible_department_id' => $department->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/services');

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('DV-TESTEXPORT-01', $content);
        $this->assertStringContainsString('Đăng ký kết hôn trực tuyến', $content);
        $this->assertStringContainsString('Hộ tịch & Nhân thân', $content);
        $this->assertStringContainsString('Phòng Tư pháp', $content);
    }

    public function test_admin_can_export_departments_csv(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        Department::factory()->create([
            'code' => 'PB-TESTEXPORT-88',
            'name' => 'Phòng Y Tế Quận',
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/departments');

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('PB-TESTEXPORT-88', $content);
        $this->assertStringContainsString('Phòng Y Tế Quận', $content);
    }

    public function test_export_respects_search_and_status_filters(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $matchingCitizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'UniqueNameForSearchTest',
            'email' => 'uniquesearch@example.com',
        ]);

        $otherCitizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'OtherCitizenName',
            'email' => 'othercitizen@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/export/citizens?search=UniqueNameForSearchTest');

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('UniqueNameForSearchTest', $content);
        $this->assertStringNotContainsString('OtherCitizenName', $content);
    }

    public function test_invalid_resource_returns_404(): void
    {
        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();

        $response = $this->actingAs($admin)->get('/admin/export/invalid_resource_type');

        $response->assertNotFound();
    }
}
