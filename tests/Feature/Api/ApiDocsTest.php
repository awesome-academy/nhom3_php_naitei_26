<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_documentation_ui_is_accessible_in_local_and_testing_environments(): void
    {
        $response = $this->get('/docs/api');

        $response->assertOk();
    }

    public function test_api_documentation_json_specification_returns_openapi_schema(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', 'Public Service Management System - API Documentation');
    }

    public function test_api_documentation_gate_restricts_access_in_production_for_unauthorized_users(): void
    {
        App::detectEnvironment(fn () => 'production');

        $guestResponse = $this->get('/docs/api');
        $guestResponse->assertForbidden();

        $citizen = User::factory()->withRole(UserRole::Citizen)->create();
        $citizenResponse = $this->actingAs($citizen)->get('/docs/api');
        $citizenResponse->assertForbidden();

        $admin = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $adminResponse = $this->actingAs($admin)->get('/docs/api');
        $adminResponse->assertOk();
    }
}
