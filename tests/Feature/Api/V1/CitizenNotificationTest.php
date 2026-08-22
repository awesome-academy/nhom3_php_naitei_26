<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_list_notifications_with_unread_count(): void
    {
        [$citizen, $application] = $this->citizenWithApplication();

        $citizen->notify(ApplicationStatusNotification::submitted($application));
        $citizen->notify(ApplicationStatusNotification::statusChanged($application, ApplicationStatus::SupplementRequired));

        $this->actingAs($citizen, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('message', 'Lấy danh sách thông báo thành công.')
            ->assertJsonPath('data.unread_count', 2)
            ->assertJsonCount(2, 'data.notifications')
            ->assertJsonFragment(['title' => 'Đã nộp hồ sơ'])
            ->assertJsonFragment(['title' => 'Cần bổ sung hồ sơ'])
            ->assertJsonFragment(['application_code' => $application->application_code]);
    }

    public function test_citizen_can_filter_unread_and_mark_notifications_as_read(): void
    {
        [$citizen, $application] = $this->citizenWithApplication();

        $citizen->notify(ApplicationStatusNotification::submitted($application));
        $citizen->notify(ApplicationStatusNotification::statusChanged($application, ApplicationStatus::Approved));

        $notification = $citizen->notifications()->oldest()->firstOrFail();
        $notification->markAsRead();

        $this->actingAs($citizen, 'sanctum')
            ->getJson('/api/v1/notifications?filter=unread')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonCount(1, 'data.notifications');

        $unread = $citizen->unreadNotifications()->firstOrFail();

        $this->actingAs($citizen, 'sanctum')
            ->patchJson("/api/v1/notifications/{$unread->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $unread->id);

        $this->assertSame(0, $citizen->fresh()->unreadNotifications()->count());
    }

    public function test_citizen_can_mark_all_notifications_as_read(): void
    {
        [$citizen, $application] = $this->citizenWithApplication();

        $citizen->notify(ApplicationStatusNotification::submitted($application));
        $citizen->notify(ApplicationStatusNotification::statusChanged($application, ApplicationStatus::Rejected));

        $this->actingAs($citizen, 'sanctum')
            ->patchJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertSame(0, $citizen->fresh()->unreadNotifications()->count());
    }

    public function test_citizen_cannot_read_another_citizens_notification(): void
    {
        [$owner, $application] = $this->citizenWithApplication();
        $other = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);

        $owner->notify(ApplicationStatusNotification::submitted($application));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertNotFound();
    }

    public function test_internal_user_cannot_use_citizen_notification_api(): void
    {
        $staff = User::factory()->withRole(UserRole::Staff)->create();

        $this->actingAs($staff, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertForbidden();
    }

    /**
     * @return array{User, Application}
     */
    private function citizenWithApplication(): array
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);

        $application = Application::query()->create([
            'application_code' => fake()->unique()->numerify('HS-20260821-#####'),
            'citizen_id' => $citizen->id,
            'service_type_id' => ServiceType::factory()->create()->id,
            'status' => ApplicationStatus::Received,
            'form_data' => ['full_name' => 'Nguyen Van A'],
            'submitted_at' => now(),
        ]);

        return [$citizen, $application];
    }
}
