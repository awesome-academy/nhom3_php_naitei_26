<?php

namespace Tests\Feature\Api\V1\Profile;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_update_allowed_profile_fields(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Nguyen Van A',
            'email' => 'citizen@example.test',
            'citizen_id' => '012345678901',
            'date_of_birth' => '1995-05-20',
            'phone' => '0901234567',
            'address' => 'Ha Noi',
            'email_notifications_enabled' => true,
        ]);

        $response = $this->actingAs($citizen)->patchJson('/api/v1/me', [
            'name' => 'Nguyen Van B',
            'date_of_birth' => '1996-06-21',
            'phone' => '0912345678',
            'address' => 'Da Nang',
            'email_notifications_enabled' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cập nhật hồ sơ thành công.')
            ->assertJsonPath('data.name', 'Nguyen Van B')
            ->assertJsonPath('data.date_of_birth', '1996-06-21')
            ->assertJsonPath('data.phone', '0912345678')
            ->assertJsonPath('data.address', 'Da Nang')
            ->assertJsonPath('data.email_notifications_enabled', false);

        $this->assertDatabaseHas(User::class, [
            'id' => $citizen->id,
            'name' => 'Nguyen Van B',
            'phone' => '0912345678',
            'address' => 'Da Nang',
            'email_notifications_enabled' => false,
        ]);

        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $citizen->id,
            'subject_id' => $citizen->id,
            'action' => 'profile.updated',
        ]);
    }

    public function test_profile_update_validation_uses_vietnamese_messages(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $this->actingAs($citizen)
            ->patchJson('/api/v1/me', [
                'name' => '',
                'date_of_birth' => 'not-a-date',
                'phone' => str_repeat('1', 31),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Dữ liệu không hợp lệ.')
            ->assertJsonPath('errors.name.0', 'Vui lòng nhập họ và tên.')
            ->assertJsonPath('errors.date_of_birth.0', 'Ngày sinh không hợp lệ.')
            ->assertJsonPath('errors.phone.0', 'Số điện thoại không được vượt quá 30 ký tự.');
    }

    public function test_citizen_can_partially_update_allowed_profile_fields(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'name' => 'Nguyen Van A',
            'phone' => '0901234567',
        ]);

        $this->actingAs($citizen)
            ->patchJson('/api/v1/me', [
                'phone' => '0912345678',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nguyen Van A')
            ->assertJsonPath('data.phone', '0912345678');
    }
}
