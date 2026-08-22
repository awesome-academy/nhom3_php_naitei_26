<?php

namespace Tests\Feature;

use App\Actions\Application\ApproveApplicationAction;
use App\Actions\Application\CreateApplicationAction;
use App\Actions\Application\StoreResultDocumentAction;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_submission_creates_citizen_notification(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
        $service = ServiceType::factory()->create();

        app(CreateApplicationAction::class)->execute($citizen, $service, ['full_name' => 'Nguyen Van A']);

        $notification = $citizen->notifications()->firstOrFail();

        $this->assertSame('application.submitted', $notification->data['event']);
        $this->assertSame('Đã nộp hồ sơ', $notification->data['title']);
    }

    public function test_status_transition_creates_citizen_notification(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $application = Application::factory()
            ->for($citizen, 'citizen')
            ->withStatus(ApplicationStatus::Processing)
            ->create();

        app(ApproveApplicationAction::class)->handle($application, $actor, 'Đã duyệt hồ sơ.');

        $notification = $citizen->notifications()->firstOrFail();

        $this->assertSame('application.approved', $notification->data['event']);
        $this->assertSame('Hồ sơ đã được duyệt', $notification->data['title']);
        $this->assertSame('approved', $notification->data['status']);
    }

    public function test_result_document_upload_creates_citizen_notification(): void
    {
        Storage::fake('local');

        $citizen = User::factory()->withRole(UserRole::Citizen)->create([
            'citizen_id' => fake()->unique()->numerify('#############'),
        ]);
        $actor = User::factory()->withRole(UserRole::SuperAdmin)->create();
        $application = Application::factory()
            ->for($citizen, 'citizen')
            ->withStatus(ApplicationStatus::Processing)
            ->create();

        app(StoreResultDocumentAction::class)->handle(
            $application,
            $actor,
            UploadedFile::fake()->create('result.pdf', 100, 'application/pdf'),
        );

        $notification = $citizen->notifications()->firstOrFail();

        $this->assertSame('application.result_document_available', $notification->data['event']);
        $this->assertSame('Có tài liệu kết quả', $notification->data['title']);
    }
}
