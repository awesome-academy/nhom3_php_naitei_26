<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertViewIs('admin.auth.login')
            ->assertSee('emblem-vietnam.svg')
            ->assertSee('Cổng Dịch Vụ Công')
            ->assertSee('PHỤC VỤ NGƯỜI DÂN')
            ->assertSee('Đăng nhập nội bộ');
    }

    public function test_internal_user_can_login_and_logout(): void
    {
        $user = User::factory()->withRole(UserRole::Staff)->create([
            'email' => 'staff@example.test',
            'password' => 'password123',
        ]);

        $this->post('/admin/login', [
            'email' => ' STAFF@EXAMPLE.TEST ',
            'password' => 'password123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'admin.login_succeeded',
        ]);

        $this->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'admin.logout',
        ]);
    }

    public function test_admin_login_validation_uses_vietnamese_messages(): void
    {
        $this->post('/admin/login', [])
            ->assertSessionHasErrors([
                'email' => 'Vui lòng nhập email.',
                'password' => 'Vui lòng nhập mật khẩu.',
            ]);
    }

    public function test_failed_admin_login_is_logged_without_revealing_account_existence(): void
    {
        $user = User::factory()->withRole(UserRole::Manager)->create([
            'email' => 'manager@example.test',
            'password' => 'password123',
        ]);

        $this->from('/admin/login')->post('/admin/login', [
            'email' => 'manager@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Thông tin đăng nhập không chính xác.']);

        $this->assertGuest();
        $this->assertDatabaseHas(ActivityLog::class, [
            'actor_id' => $user->id,
            'action' => 'admin.login_failed',
        ]);
    }
}
