<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1StandardizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_standard_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'API is running',
                'data' => [
                    'status' => 'ok',
                ],
            ]);
    }

    public function test_service_catalog_index_returns_standard_success_envelope_with_pagination(): void
    {
        $category = ServiceCategory::factory()->create();
        ServiceType::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/services');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'category_id',
                            'name',
                            'code',
                            'description',
                            'requirements',
                            'form_schema',
                            'document_requirements',
                            'processing_time_days',
                            'fee',
                            'is_active',
                        ],
                    ],
                    'links',
                    'meta',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Services retrieved successfully');
    }

    public function test_service_catalog_show_returns_standard_success_envelope(): void
    {
        $service = ServiceType::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/services/{$service->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'category_id',
                    'name',
                    'code',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $service->id);
    }

    public function test_service_catalog_categories_returns_standard_success_envelope(): void
    {
        ServiceCategory::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/services/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'code', 'description'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Service categories retrieved successfully');
    }

    public function test_non_existent_service_returns_standard_404_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/services/999999');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Không tìm thấy tài nguyên.');
    }

    public function test_unauthenticated_request_to_protected_endpoint_returns_standard_401_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/applications');

        $response->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Chưa đăng nhập.',
                'errors' => null,
            ]);
    }

    public function test_authenticated_citizen_can_list_applications_with_standard_envelope(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();
        $service = ServiceType::factory()->create();
        Application::factory()->count(2)->create([
            'citizen_id' => $citizen->id,
            'service_type_id' => $service->id,
        ]);

        Sanctum::actingAs($citizen, ['*']);

        $response = $this->getJson('/api/v1/applications');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'application_code',
                            'status',
                            'service_type',
                        ],
                    ],
                    'links',
                    'meta',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Applications retrieved successfully');
    }

    public function test_citizen_cannot_view_another_citizens_application_returns_standard_403_error_envelope(): void
    {
        $citizen1 = User::factory()->withRole(UserRole::Citizen)->create();
        $citizen2 = User::factory()->withRole(UserRole::Citizen)->create();
        $application = Application::factory()->create([
            'citizen_id' => $citizen1->id,
        ]);

        Sanctum::actingAs($citizen2, ['*']);

        $response = $this->getJson("/api/v1/applications/{$application->id}");

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bạn không có quyền thực hiện thao tác này.');
    }

    public function test_validation_error_returns_standard_422_error_envelope(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();
        Sanctum::actingAs($citizen, ['*']);

        $response = $this->postJson('/api/v1/applications', []);

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJsonPath('success', false);
    }
}
