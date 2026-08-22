<?php

namespace Tests\Feature;

use App\Actions\Application\ApproveApplicationAction;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApplicationWorkflowEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_major_application_status_notification_uses_database_and_mail_channels_when_enabled(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'email_notifications_enabled' => true,
        ]);
        $application = Application::factory()->for($citizen, 'citizen')->create();

        $notification = ApplicationStatusNotification::statusChanged($application, ApplicationStatus::Approved);

        $this->assertSame(['database', 'mail'], $notification->via($citizen));
        $this->assertSame('Hồ sơ đã được duyệt', $notification->toMail($citizen)->subject);
    }

    public function test_minor_application_status_notification_only_uses_database_channel(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'email_notifications_enabled' => true,
        ]);
        $application = Application::factory()->for($citizen, 'citizen')->create();

        $notification = ApplicationStatusNotification::statusChanged($application, ApplicationStatus::Processing);

        $this->assertSame(['database'], $notification->via($citizen));
    }

    public function test_citizen_can_disable_email_delivery_without_disabling_in_app_notifications(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'email_notifications_enabled' => false,
        ]);
        $application = Application::factory()->for($citizen, 'citizen')->create();

        $notification = ApplicationStatusNotification::statusChanged($application, ApplicationStatus::SupplementRequired);

        $this->assertSame(['database'], $notification->via($citizen));
    }

    public function test_workflow_prepares_mail_channel_for_major_status_change(): void
    {
        Notification::fake();

        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'email_notifications_enabled' => true,
        ]);
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $application = Application::factory()
            ->for($citizen, 'citizen')
            ->withStatus(ApplicationStatus::Processing)
            ->create();

        app(ApproveApplicationAction::class)->handle($application, $actor, 'Đã duyệt hồ sơ.');

        Notification::assertSentTo(
            $citizen,
            ApplicationStatusNotification::class,
            fn (ApplicationStatusNotification $notification): bool => $notification->via($citizen) === ['database', 'mail'],
        );
    }
}
