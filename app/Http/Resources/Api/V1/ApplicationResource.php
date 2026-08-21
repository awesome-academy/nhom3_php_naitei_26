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

        $isOwnerOrInternal = $actor !== null && (
            $actor->id === $this->citizen_id
            || in_array($actor->role->value, ['staff', 'manager', 'super_admin'], true)
        );

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
            'documents' => ApplicationDocumentResource::collection($this->whenLoaded('documents')),
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
                $isOwnerOrInternal,
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
                $this->whenLoaded('statusHistories', fn () => $this->statusHistories
                    ->sortBy(fn ($history) => [$history->created_at?->timestamp ?? 0, $history->getKey()])
                    ->map(fn ($history) => [
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
                        'note' => $history->note,
                        'created_at' => $history->created_at?->toISOString(),
                    ])
                    ->values())
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
