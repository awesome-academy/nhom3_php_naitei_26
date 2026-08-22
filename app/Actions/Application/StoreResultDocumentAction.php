<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Support\Application\ApplicationWorkflowNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class StoreResultDocumentAction
{
    public function __construct(
        private ApplicationWorkflowNotifier $workflowNotifier,
    ) {}

    public function handle(Application $application, User $actor, UploadedFile $file, ?string $requirementCode = null): ApplicationDocument
    {
        return DB::transaction(function () use ($application, $actor, $file, $requirementCode): ApplicationDocument {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            Gate::forUser($actor)->authorize('uploadResultDocument', $locked);

            if ($locked->status !== ApplicationStatus::Processing) {
                throw ValidationException::withMessages([
                    'document' => 'Chỉ có thể đính kèm tài liệu kết quả khi hồ sơ đang xử lý.',
                ]);
            }

            $path = $file->store('applications/'.$locked->getKey(), 'local');

            $document = ApplicationDocument::query()->create([
                'application_id' => $locked->getKey(),
                'uploaded_by' => $actor->getKey(),
                'document_kind' => DocumentKind::Result,
                'original_name' => $file->getClientOriginalName(),
                'requirement_code' => $requirementCode,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            $this->workflowNotifier->resultDocumentAvailable($locked, $document, $actor);

            return $document;
        });
    }
}
