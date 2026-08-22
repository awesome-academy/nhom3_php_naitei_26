<?php

namespace App\Support\Application;

use App\Enums\ApplicationStatus;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Http\Request;

final class ApplicationActivityLogger
{
    public const ASSIGNED = 'application.assigned';

    public const CLAIMED = 'application.claimed';

    public const PROCESSING_STARTED = 'application.processing_started';

    public const SUPPLEMENT_REQUESTED = 'application.supplement_requested';

    public const PROCESSING_RESUMED = 'application.processing_resumed';

    public const APPROVED = 'application.approved';

    public const REJECTED = 'application.rejected';

    public const RESULT_DOCUMENT_UPLOADED = 'application.result_document_uploaded';

    public const SUBMITTED_FOR_APPROVAL = 'application.submitted_for_approval';

    public const RETURNED_TO_PROCESSING = 'application.returned_to_processing';

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        Application $application,
        User $actor,
        array $metadata = [],
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();

        return ActivityLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => $application->getMorphClass(),
            'subject_id' => $application->getKey(),
            'description' => $this->descriptionFor($action, $application),
            'metadata' => [
                ...$metadata,
                'actor' => $this->userSnapshot($actor),
                'application' => $this->applicationSnapshot($application),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function recordAssignment(Application $application, User $actor, User $staff, ?string $note): ActivityLog
    {
        return $this->record(self::ASSIGNED, $application, $actor, [
            'assigned_staff' => $this->userSnapshot($staff),
            'note_present' => $note !== null && trim($note) !== '',
        ]);
    }

    public function recordClaim(Application $application, User $actor): ActivityLog
    {
        return $this->record(self::CLAIMED, $application, $actor, [
            'assigned_staff' => $this->userSnapshot($actor),
        ]);
    }

    public function recordStatusChange(
        Application $application,
        User $actor,
        ApplicationStatus $from,
        ApplicationStatus $to,
        ?string $note,
    ): ActivityLog {
        return $this->record($this->actionForTransition($from, $to), $application, $actor, [
            'from_status' => $from->value,
            'from_status_label' => $from->label(),
            'to_status' => $to->value,
            'to_status_label' => $to->label(),
            'note_present' => $note !== null && trim($note) !== '',
        ]);
    }

    public function recordResultDocument(Application $application, User $actor, ApplicationDocument $document): ActivityLog
    {
        return $this->record(self::RESULT_DOCUMENT_UPLOADED, $application, $actor, [
            'document' => [
                'id' => $document->getKey(),
                'kind' => $document->document_kind->value,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'requirement_code' => $document->requirement_code,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationSnapshot(Application $application): array
    {
        $application->loadMissing([
            'historicalCitizen',
            'historicalAssignedStaff',
            'historicalServiceType.historicalResponsibleDepartment',
        ]);

        return [
            'id' => $application->getKey(),
            'application_code' => $application->application_code,
            'status' => $application->status->value,
            'status_label' => $application->status->label(),
            'citizen' => $application->historicalCitizen === null ? null : [
                'id' => $application->historicalCitizen->getKey(),
                'name' => $application->historicalCitizen->name,
                'email' => $application->historicalCitizen->email,
            ],
            'service_type' => $application->historicalServiceType === null ? null : [
                'id' => $application->historicalServiceType->getKey(),
                'name' => $application->historicalServiceType->name,
                'code' => $application->historicalServiceType->code,
            ],
            'department' => $application->historicalServiceType?->historicalResponsibleDepartment === null ? null : [
                'id' => $application->historicalServiceType->historicalResponsibleDepartment->getKey(),
                'name' => $application->historicalServiceType->historicalResponsibleDepartment->name,
                'code' => $application->historicalServiceType->historicalResponsibleDepartment->code,
            ],
            'assigned_staff' => $application->historicalAssignedStaff === null
                ? null
                : $this->userSnapshot($application->historicalAssignedStaff),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userSnapshot(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
            'archived_at' => $user->deleted_at?->toISOString(),
        ];
    }

    public function actionForTransition(ApplicationStatus $from, ApplicationStatus $to): string
    {
        return match (true) {
            $to === ApplicationStatus::Processing && $from === ApplicationStatus::PendingApproval => self::RETURNED_TO_PROCESSING,
            $to === ApplicationStatus::Processing && $from === ApplicationStatus::SupplementRequired => self::PROCESSING_RESUMED,
            $to === ApplicationStatus::Processing => self::PROCESSING_STARTED,
            $to === ApplicationStatus::SupplementRequired => self::SUPPLEMENT_REQUESTED,
            $to === ApplicationStatus::PendingApproval => self::SUBMITTED_FOR_APPROVAL,
            $to === ApplicationStatus::Approved => self::APPROVED,
            $to === ApplicationStatus::Rejected => self::REJECTED,
            $to === ApplicationStatus::Assigned => self::PROCESSING_STARTED,
            default => self::PROCESSING_STARTED,
        };
    }

    private function descriptionFor(string $action, Application $application): string
    {
        return match ($action) {
            self::ASSIGNED => "Phân công hồ sơ {$application->application_code}.",
            self::CLAIMED => "Nhận xử lý hồ sơ {$application->application_code}.",
            self::PROCESSING_STARTED => "Bắt đầu xử lý hồ sơ {$application->application_code}.",
            self::SUPPLEMENT_REQUESTED => "Yêu cầu bổ sung hồ sơ {$application->application_code}.",
            self::PROCESSING_RESUMED => "Tiếp tục xử lý hồ sơ {$application->application_code}.",
            self::APPROVED => "Duyệt hồ sơ {$application->application_code}.",
            self::REJECTED => "Từ chối hồ sơ {$application->application_code}.",
            self::SUBMITTED_FOR_APPROVAL => "Gửi hồ sơ {$application->application_code} chờ duyệt.",
            self::RETURNED_TO_PROCESSING => "Trả hồ sơ {$application->application_code} về xử lý lại.",
            self::RESULT_DOCUMENT_UPLOADED => "Đính kèm tài liệu kết quả cho hồ sơ {$application->application_code}.",
            default => "Cập nhật hồ sơ {$application->application_code}.",
        };
    }
}
