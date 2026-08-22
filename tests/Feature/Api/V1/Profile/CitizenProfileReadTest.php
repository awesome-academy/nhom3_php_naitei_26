<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenProfileReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_read_current_profile(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Nguyen Van A',
            'email' => 'citizen@example.test',
            'citizen_id' => '012345678901',
            'date_of_birth' => '1995-05-20',
            'phone' => '0901234567',
            'address' => 'Ha Noi',
            'email_notifications_enabled' => false,
        ]);

        $this->actingAs($citizen)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Lấy thông tin hồ sơ thành công.')
            ->assertJsonPath('data.id', $citizen->id)
            ->assertJsonPath('data.email', 'citizen@example.test')
            ->assertJsonPath('data.role', UserRole::Citizen->value)
            ->assertJsonPath('data.citizen_id', '012345678901')
            ->assertJsonPath('data.date_of_birth', '1995-05-20')
            ->assertJsonPath('data.phone', '0901234567')
            ->assertJsonPath('data.address', 'Ha Noi')
            ->assertJsonPath('data.email_notifications_enabled', false);
    }

    public function test_guest_cannot_read_current_profile(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Chưa đăng nhập.');
    }

    public function test_internal_user_cannot_read_citizen_profile_endpoint(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $this->actingAs($staff)
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bạn không có quyền truy cập tài nguyên này.');
    }
}
