<?php

namespace Tests\Feature\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Application\ApplicationActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationWorkflowAuditTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $manager;

    private User $staff;

    private ServiceType $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->manager = User::factory()->manager()->create();
        $this->department->update(['leader_id' => $this->manager->id]);
        $this->department->users()->syncWithoutDetaching([$this->manager->id]);

        $this->staff = User::factory()->staff()->create();
        $this->department->users()->syncWithoutDetaching([$this->staff->id]);

        $this->service = ServiceType::factory()->create([
            'responsible_department_id' => $this->department->id,
        ]);
    }

    public function test_assignment_and_claim_create_application_audit_logs(): void
    {
        $assignedApplication = Application::factory()->create(['service_type_id' => $this->service->id]);
        $claimedApplication = Application::factory()->create(['service_type_id' => $this->service->id]);

        $this->actingAs($this->manager)->post(
            route('admin.applications.assign', $assignedApplication),
            ['staff_id' => $this->staff->id, 'note' => 'Phân công kiểm tra'],
        )->assertRedirect(route('admin.applications.show', $assignedApplication));

        $this->actingAs($this->staff)->post(
            route('admin.applications.claim', $claimedApplication),
        )->assertRedirect(route('admin.applications.show', $claimedApplication));

        $assignedLog = ActivityLog::query()
            ->where('action', ApplicationActivityLogger::ASSIGNED)
            ->where('subject_id', $assignedApplication->id)
            ->firstOrFail();

        $this->assertSame($this->manager->id, $assignedLog->actor_id);
        $this->assertSame($assignedApplication->application_code, $assignedLog->metadata['application']['application_code']);
        $this->assertSame($this->staff->id, $assignedLog->metadata['assigned_staff']['id']);
        $this->assertTrue($assignedLog->metadata['note_present']);
        $this->assertArrayNotHasKey('form_data', $assignedLog->metadata['application']);
        $this->assertArrayNotHasKey('citizen_id', $assignedLog->metadata['application']['citizen']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ApplicationActivityLogger::CLAIMED,
            'actor_id' => $this->staff->id,
            'subject_type' => $claimedApplication->getMorphClass(),
            'subject_id' => $claimedApplication->id,
        ]);
    }

    public function test_status_transitions_create_specific_application_audit_logs(): void
    {
        $application = Application::factory()->create([
            'service_type_id' => $this->service->id,
            'assigned_staff_id' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)->post(route('admin.applications.start-processing', $application))
            ->assertRedirect(route('admin.applications.show', $application));
        $this->actingAs($this->staff)->post(
            route('admin.applications.request-supplement', $application),
            ['note' => 'Cần bổ sung giấy tờ'],
        )->assertRedirect(route('admin.applications.show', $application));
        $this->actingAs($this->staff)->post(route('admin.applications.resume', $application))
            ->assertRedirect(route('admin.applications.show', $application));
        $this->actingAs($this->staff)->post(
            route('admin.applications.approve', $application),
            ['result_note' => 'Đạt yêu cầu'],
        )->assertRedirect(route('admin.applications.show', $application));

        foreach ([
            ApplicationActivityLogger::PROCESSING_STARTED,
            ApplicationActivityLogger::SUPPLEMENT_REQUESTED,
            ApplicationActivityLogger::PROCESSING_RESUMED,
            ApplicationActivityLogger::APPROVED,
        ] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'action' => $action,
                'actor_id' => $this->staff->id,
                'subject_id' => $application->id,
            ]);
        }

        $supplementLog = ActivityLog::query()
            ->where('action', ApplicationActivityLogger::SUPPLEMENT_REQUESTED)
            ->firstOrFail();

        $this->assertSame(ApplicationStatus::Processing->value, $supplementLog->metadata['from_status']);
        $this->assertSame(ApplicationStatus::SupplementRequired->value, $supplementLog->metadata['to_status']);
        $this->assertTrue($supplementLog->metadata['note_present']);
    }

    public function test_rejection_and_result_document_upload_create_application_audit_logs(): void
    {
        Storage::fake('local');

        $rejectedApplication = Application::factory()->create([
            'service_type_id' => $this->service->id,
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);
        $resultApplication = Application::factory()->create([
            'service_type_id' => $this->service->id,
            'assigned_staff_id' => $this->staff->id,
            'status' => ApplicationStatus::Processing,
            'processing_started_at' => now(),
        ]);

        $this->actingAs($this->staff)->post(
            route('admin.applications.reject', $rejectedApplication),
            ['rejection_reason' => 'Không đủ điều kiện'],
        )->assertRedirect(route('admin.applications.show', $rejectedApplication));

        $this->actingAs($this->staff)->post(
            route('admin.applications.result-documents.store', $resultApplication),
            ['document' => UploadedFile::fake()->create('ket-qua.pdf', 100, 'application/pdf')],
        )->assertRedirect(route('admin.applications.show', $resultApplication));

        $this->assertDatabaseHas('activity_logs', [
            'action' => ApplicationActivityLogger::REJECTED,
            'actor_id' => $this->staff->id,
            'subject_id' => $rejectedApplication->id,
        ]);

        $documentLog = ActivityLog::query()
            ->where('action', ApplicationActivityLogger::RESULT_DOCUMENT_UPLOADED)
            ->where('subject_id', $resultApplication->id)
            ->firstOrFail();

        $this->assertSame('ket-qua.pdf', $documentLog->metadata['document']['original_name']);
        $this->assertArrayNotHasKey('path', $documentLog->metadata['document']);
        $this->assertArrayNotHasKey('form_data', $documentLog->metadata['application']);
    }

    public function test_citizen_cannot_access_admin_activity_log(): void
    {
        $citizen = User::factory()->withRole(UserRole::Citizen)->create();

        $this->actingAs($citizen)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }
}
