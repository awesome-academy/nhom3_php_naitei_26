<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ApplicationStatus;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Support\Application\ApplicationStatusPresenter;
use App\Support\ServiceSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->relationLoaded('documents')) {
            $this->documents->each(
                fn (ApplicationDocument $document) => $document->setRelation('application', $this->resource)
            );
        }

        /** @var User|null $actor */
        $actor = $request->user();

        $isInternal = $actor !== null && in_array($actor->role->value, ['staff', 'manager', 'super_admin'], true);
        $isOwner = $actor !== null && $actor->id === $this->citizen_id;
        $isOwnerOrInternal = $isOwner || $isInternal;

        // Citizen chỉ thấy các mốc công khai, ẩn Assigned/PendingApproval và ghi chú nội bộ
        $citizenVisibleStatuses = [
            ApplicationStatus::Received,
            ApplicationStatus::Processing,
            ApplicationStatus::SupplementRequired,
            ApplicationStatus::Approved,
            ApplicationStatus::Rejected,
        ];

        return [
            'id' => $this->id,
            'application_code' => $this->application_code,
            'service_type' => [
                'id' => $this->service_type_id,
                'name' => $this->whenLoaded('serviceType', fn () => $this->serviceType->name),
                'code' => $this->whenLoaded('serviceType', fn () => $this->serviceType->code),
                'document_requirements' => $this->whenLoaded('serviceType', fn () => ServiceSchema::normalizeDocumentRequirements($this->serviceType->document_requirements)),
            ],
            'status' => $this->status->value,
            'form_data' => $this->form_data,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'missing_required_documents' => $this->missingRequiredDocuments(),
            'documents' => ApplicationDocumentResource::collection($this->whenLoaded('documents', function () use ($isInternal) {
                $docs = $this->documents;
                // Citizen chưa duyệt thì không thấy tài liệu kết quả
                if (! $isInternal && ! $this->status->isTerminal()) {
                    $docs = $docs->filter(fn (ApplicationDocument $doc) => $doc->document_kind->value !== 'result');
                }

                // Khi đã duyệt thì chỉ hiện kết quả đã duyệt, ẩn pending nội bộ cũng đã filter ở trên
                return $docs->values();
            })),
            'processing_started_at' => $this->when($isOwnerOrInternal, $this->processing_started_at?->toISOString()),
            'completed_at' => $this->when($isOwnerOrInternal, $this->completed_at?->toISOString()),
            'result_note' => $this->when(
                $isOwnerOrInternal && $this->status === ApplicationStatus::Approved,
                $this->result_note
            ),
            'rejection_reason' => $this->when(
                $isOwnerOrInternal && $this->status === ApplicationStatus::Rejected,
                $this->rejection_reason
            ),
            'assigned_staff' => $this->when(
                $isInternal,
                $this->whenLoaded('assignedStaff', fn () => $this->assignedStaff !== null ? [
                    'id' => $this->assignedStaff->id,
                    'name' => $this->assignedStaff->name,
                ] : null)
            ),
            'supplement_note' => $this->when(
                $isOwnerOrInternal,
                $this->whenLoaded('statusHistories', fn () => $this->supplementNote())
            ),
            'timeline' => $this->when(
                $isOwnerOrInternal,
                $this->whenLoaded('statusHistories', function () use ($isInternal, $citizenVisibleStatuses) {
                    $histories = $this->statusHistories
                        ->sortBy(fn ($history) => [$history->created_at?->timestamp ?? 0, $history->getKey()]);

                    // Citizen: ẩn các trạng thái nội bộ và ghi chú nội bộ
                    if (! $isInternal) {
                        $histories = $histories->filter(fn ($history) => in_array($history->to_status, $citizenVisibleStatuses, true));
                    }

                    return $histories
                        ->map(function ($history) use ($isInternal) {
                            // Citizen chỉ thấy note khi là yêu cầu bổ sung / duyệt / từ chối (ẩn ghi chú nội bộ như chuyển duyệt)
                            $note = $history->note;
                            if (! $isInternal) {
                                $noteVisibleStatuses = [ApplicationStatus::SupplementRequired, ApplicationStatus::Rejected, ApplicationStatus::Approved];
                                if (! in_array($history->to_status, $noteVisibleStatuses, true)) {
                                    $note = null;
                                }
                            }

                            return [
                                'from_status' => $history->from_status?->value,
                                'from_status_label' => $history->from_status === null
                                    ? null
                                    : ApplicationStatusPresenter::label($history->from_status),
                                'to_status' => $history->to_status->value,
                                'to_status_label' => ApplicationStatusPresenter::label($history->to_status),
                                'label' => ApplicationStatusPresenter::label($history->to_status),
                                'description' => ApplicationStatusPresenter::transitionDescription(
                                    $history->from_status,
                                    $history->to_status
                                ),
                                'changed_by_name' => $history->changedBy?->name,
                                'note' => $note,
                                'created_at' => $history->created_at?->toISOString(),
                            ];
                        })
                        ->values();
                })
            ),
        ];
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    private function missingRequiredDocuments(): array
    {
        return $this->resource->missingRequiredDocuments();
    }
}
