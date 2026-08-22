<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Manager,
        ]);
    }

    public function test_admin_can_list_service_categories(): void
    {
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.service-categories.index'));

        $response->assertOk()
            ->assertSee($category->name);
    }

    public function test_admin_can_create_service_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.service-categories.store'), [
            'name' => 'New Category',
            'code' => 'NC01',
            'description' => 'Test description',
        ]);

        $response->assertRedirect(route('admin.service-categories.index'));
        $this->assertDatabaseHas('service_categories', [
            'name' => 'New Category',
            'code' => 'NC01',
        ]);
    }

    public function test_admin_can_update_service_category(): void
    {
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('admin.service-categories.update', $category), [
            'name' => 'Updated Category',
            'code' => $category->code,
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.service-categories.index'));
        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_admin_can_list_service_types(): void
    {
        $type = ServiceType::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.service-types.index'));

        $response->assertOk()
            ->assertSee($type->name);
    }

    public function test_admin_can_create_service_type(): void
    {
        $category = ServiceCategory::factory()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.service-types.store'), [
            'category_id' => $category->id,
            'responsible_department_id' => $department->id,
            'name' => 'New Service Type',
            'code' => 'NST01',
            'description' => 'A new service',
            'processing_time_days' => 5,
            'fee' => 100,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'name' => 'New Service Type',
            'code' => 'NST01',
            'processing_time_days' => 5,
        ]);
    }

    public function test_admin_can_update_service_type(): void
    {
        $type = ServiceType::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('admin.service-types.update', $type), [
            'category_id' => $type->category_id,
            'responsible_department_id' => $type->responsible_department_id,
            'name' => 'Updated Service Type',
            'code' => $type->code,
            'description' => $type->description,
            'processing_time_days' => 10,
            'fee' => $type->fee,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'id' => $type->id,
            'name' => 'Updated Service Type',
            'processing_time_days' => 10,
        ]);
    }

    public function test_admin_can_create_service_type_with_typed_document_requirements(): void
    {
        $category = ServiceCategory::factory()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.service-types.store'), [
            'category_id' => $category->id,
            'responsible_department_id' => $department->id,
            'name' => 'Service With Documents',
            'code' => 'SWD01',
            'processing_time_days' => 5,
            'fee' => 100,
            'is_active' => true,
            'document_requirements' => [
                ['name' => 'Bản sao CCCD', 'is_required' => true, 'type' => 'pdf'],
                ['name' => 'Sổ hộ khẩu', 'is_required' => false, 'type' => 'image'],
                ['name' => 'Đơn đề nghị', 'is_required' => true, 'type' => 'mixed'],
                ['name' => 'CCCD', 'is_required' => false, 'type' => 'pdf'],
            ],
        ]);

        $response->assertRedirect(route('admin.service-types.index'));

        $serviceType = ServiceType::query()->where('code', 'SWD01')->firstOrFail();

        $this->assertSame([
            ['code' => 'ban-sao-cccd', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
            ['code' => 'so-ho-khau', 'label' => 'Sổ hộ khẩu', 'required' => false, 'type' => 'image'],
            ['code' => 'don-de-nghi', 'label' => 'Đơn đề nghị', 'required' => true, 'type' => 'mixed'],
            ['code' => 'cccd', 'label' => 'CCCD', 'required' => false, 'type' => 'pdf'],
        ], $serviceType->document_requirements);
    }

    public function test_admin_can_update_service_type_and_keep_requirement_codes(): void
    {
        $type = ServiceType::factory()->create([
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
            ],
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.service-types.update', $type), [
            'category_id' => $type->category_id,
            'responsible_department_id' => $type->responsible_department_id,
            'name' => 'Updated Service Type',
            'code' => $type->code,
            'description' => $type->description,
            'processing_time_days' => 10,
            'fee' => $type->fee,
            'is_active' => true,
            'document_requirements' => [
                ['code' => 'citizen_id_copy', 'name' => 'Bản sao CCCD', 'is_required' => true, 'type' => 'pdf'],
                ['name' => 'Giấy xác nhận', 'is_required' => false, 'type' => 'image'],
            ],
        ]);

        $response->assertRedirect(route('admin.service-types.index'));

        $type->refresh();

        $this->assertSame([
            ['code' => 'citizen_id_copy', 'label' => 'Bản sao CCCD', 'required' => true, 'type' => 'pdf'],
            ['code' => 'giay-xac-nhan', 'label' => 'Giấy xác nhận', 'required' => false, 'type' => 'image'],
        ], $type->document_requirements);
    }

    public function test_document_requirement_with_invalid_type_is_rejected(): void
    {
        $category = ServiceCategory::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.service-types.store'), [
                'category_id' => $category->id,
                'name' => 'Invalid Docs',
                'code' => 'INV01',
                'processing_time_days' => 5,
                'fee' => 100,
                'document_requirements' => [
                    ['name' => 'CCCD', 'is_required' => true, 'type' => 'docx'],
                ],
            ])
            ->assertSessionHasErrors(['document_requirements.0.type']);

        $this->assertDatabaseMissing('service_types', ['code' => 'INV01']);
    }

    public function test_admin_can_archive_and_restore_service_category(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $category = ServiceCategory::factory()->create(['name' => 'Category To Archive']);

        // Archive
        $response = $this->actingAs($superAdmin)->delete(route('admin.service-categories.destroy', $category));
        $response->assertRedirect(route('admin.service-categories.index'));
        $this->assertSoftDeleted('service_categories', ['id' => $category->id]);

        // Default index (active) should not show archived category
        $indexResponse = $this->actingAs($superAdmin)->get(route('admin.service-categories.index'));
        $indexResponse->assertOk()->assertDontSee('Category To Archive');

        // Archived filter should show archived category
        $archivedResponse = $this->actingAs($superAdmin)->get(route('admin.service-categories.index', ['status' => 'archived']));
        $archivedResponse->assertOk()->assertSee('Category To Archive');

        // Restore
        $restoreResponse = $this->actingAs($superAdmin)->post(route('admin.service-categories.restore', $category));
        $restoreResponse->assertRedirect(route('admin.service-categories.index', ['status' => 'archived']));
        $this->assertNotSoftDeleted('service_categories', ['id' => $category->id]);
    }

    public function test_admin_can_archive_and_restore_service_type(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $serviceType = ServiceType::factory()->create(['name' => 'Service To Archive']);

        // Archive
        $response = $this->actingAs($superAdmin)->delete(route('admin.service-types.destroy', $serviceType));
        $response->assertRedirect(route('admin.service-types.index'));
        $this->assertSoftDeleted('service_types', ['id' => $serviceType->id]);

        // Default index (active) should not show archived service
        $indexResponse = $this->actingAs($superAdmin)->get(route('admin.service-types.index'));
        $indexResponse->assertOk()->assertDontSee('Service To Archive');

        // Archived filter should show archived service
        $archivedResponse = $this->actingAs($superAdmin)->get(route('admin.service-types.index', ['status' => 'archived']));
        $archivedResponse->assertOk()->assertSee('Service To Archive');

        // Restore
        $restoreResponse = $this->actingAs($superAdmin)->post(route('admin.service-types.restore', $serviceType));
        $restoreResponse->assertRedirect(route('admin.service-types.index', ['status' => 'archived']));
        $this->assertNotSoftDeleted('service_types', ['id' => $serviceType->id]);
    }
}
