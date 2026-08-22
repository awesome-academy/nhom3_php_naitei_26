<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Actions\Application\ApproveApplicationAction;
use App\Actions\Application\AssignApplicationAction;
use App\Actions\Application\ClaimApplicationAction;
use App\Actions\Application\RejectApplicationAction;
use App\Actions\Application\RequestSupplementAction;
use App\Actions\Application\ResumeProcessingAction;
use App\Actions\Application\ReturnToProcessingAction;
use App\Actions\Application\StartProcessingAction;
use App\Actions\Application\StoreResultDocumentAction;
use App\Actions\Application\SubmitForApprovalAction;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Applications\ApproveApplicationRequest;
use App\Http\Requests\Admin\Applications\AssignApplicationRequest;
use App\Http\Requests\Admin\Applications\ListApplicationsRequest;
use App\Http\Requests\Admin\Applications\RejectApplicationRequest;
use App\Http\Requests\Admin\Applications\RequestSupplementRequest;
use App\Http\Requests\Admin\Applications\ReturnToProcessingRequest;
use App\Http\Requests\Admin\Applications\StoreResultDocumentRequest;
use App\Http\Requests\Admin\Applications\SubmitForApprovalRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(ListApplicationsRequest $request): View|RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();
        $visibleScope = Application::query()->visibleTo($actor);
        $authorizedApplicationCount = (clone $visibleScope)->count();
        [$serviceOptions, $departmentOptions, $staffOptions] = $this->applicationFilterOptions($visibleScope);

        $query = (clone $visibleScope)
            ->with([
                'serviceType' => fn ($serviceQuery) => $serviceQuery
                    ->withTrashed()
                    ->with(['responsibleDepartment' => fn ($departmentQuery) => $departmentQuery->withTrashed()]),
                'citizen' => fn ($citizenQuery) => $citizenQuery->withTrashed(),
                'assignedStaff' => fn ($staffQuery) => $staffQuery->withTrashed(),
            ])
            ->searchForAdmin($filters['q'] ?? null)
            ->withAdminStatus($filters['status'] ?? null)
            ->forService(isset($filters['service_type_id']) ? (int) $filters['service_type_id'] : null)
            ->forDepartment(isset($filters['department_id']) ? (int) $filters['department_id'] : null)
            ->assignedToStaff(isset($filters['assigned_staff_id']) ? (int) $filters['assigned_staff_id'] : null)
            ->submittedBetween($filters['submitted_from'] ?? null, $filters['submitted_to'] ?? null)
            ->when($request->boolean('overdue'), fn (Builder $overdueQuery): Builder => $overdueQuery->overdue())
            ->sortForAdmin($filters['sort']);

        $queryParameters = $this->applicationQueryParameters($filters, $request->boolean('overdue'));
        $applications = $query->paginate(20)->appends($queryParameters);

        if ($applications->total() > 0 && $applications->currentPage() > $applications->lastPage()) {
            return redirect()->route('admin.applications.index', [
                ...$queryParameters,
                'page' => $applications->lastPage(),
            ]);
        }

        $claimable = $actor->isStaff()
            ? Application::query()->claimableBy($actor)->count()
            : 0;

        $claimableApplications = $actor->isStaff()
            ? Application::query()
                ->with(['serviceType.responsibleDepartment', 'citizen', 'assignedStaff'])
                ->claimableBy($actor)
                ->orderByDesc('submitted_at')
                ->limit(10)
                ->get()
            : collect();

        $stats = null;

        if ($actor->isManager() || $actor->isSuperAdmin()) {
            $stats = [
                'pending' => (clone $visibleScope)->whereIn('status', [
                    ApplicationStatus::Received,
                    ApplicationStatus::Assigned,
                    ApplicationStatus::Processing,
                    ApplicationStatus::SupplementRequired,
                    ApplicationStatus::PendingApproval,
                ])->count(),
                'overdue' => (clone $visibleScope)->overdue()->count(),
            ];
        }

        $statusOptions = collect(ApplicationStatus::cases())
            ->map(fn (ApplicationStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->push([
                'value' => ApplicationStatus::COMPLETED_FILTER,
                'label' => 'Đã hoàn thành',
            ]);
        $sortOptions = ApplicationStatus::sortOptions();
        $hasFilters = collect([
            $filters['q'] ?? null,
            $filters['status'] ?? null,
            $filters['service_type_id'] ?? null,
            $filters['department_id'] ?? null,
            $filters['assigned_staff_id'] ?? null,
            $filters['submitted_from'] ?? null,
            $filters['submitted_to'] ?? null,
            $request->boolean('overdue') ? true : null,
            $filters['sort'] !== ApplicationStatus::defaultSort() ? $filters['sort'] : null,
        ])->contains(fn (mixed $value): bool => $value !== null && $value !== '');

        return view('admin.applications.index', compact(
            'applications',
            'authorizedApplicationCount',
            'claimable',
            'claimableApplications',
            'departmentOptions',
            'filters',
            'hasFilters',
            'serviceOptions',
            'sortOptions',
            'staffOptions',
            'stats',
            'statusOptions',
        ));
    }

    /**
     * @return array{Collection<int, ServiceType>, Collection<int, Department>, Collection<int, User>}
     */
    private function applicationFilterOptions(Builder $visibleScope): array
    {
        $serviceIds = (clone $visibleScope)
            ->whereNotNull('service_type_id')
            ->distinct()
            ->pluck('service_type_id');
        $serviceOptions = ServiceType::withTrashed()
            ->whereIn('id', $serviceIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'code', 'responsible_department_id', 'is_active', 'deleted_at']);
        $departmentOptions = Department::withTrashed()
            ->whereIn('id', $serviceOptions->pluck('responsible_department_id')->filter()->unique())
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'code', 'deleted_at']);
        $staffIds = (clone $visibleScope)
            ->whereNotNull('assigned_staff_id')
            ->distinct()
            ->pluck('assigned_staff_id');
        $staffOptions = User::withTrashed()
            ->whereIn('id', $staffIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'is_active', 'deleted_at']);

        return [$serviceOptions, $departmentOptions, $staffOptions];
    }

    /** @return array<string, mixed> */
    private function applicationQueryParameters(array $filters, bool $overdue): array
    {
        $parameters = collect($filters)
            ->except('page')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();

        if (! $overdue) {
            unset($parameters['overdue']);
        } else {
            $parameters['overdue'] = 1;
        }

        return $parameters;
    }

    public function show(Application $application): View
    {
        $this->authorize('view', $application);

        $application->load([
            'historicalCitizen',
            'historicalAssignedStaff',
            'historicalServiceType.historicalResponsibleDepartment',
            'serviceType.responsibleDepartment.users',
            'documents.uploader',
            'assignments.staff',
            'assignments.assignedBy',
            'assignments.department',
            'statusHistories.changedBy',
        ]);

        return view('admin.applications.show', compact('application'));
    }

    public function assign(
        AssignApplicationRequest $request,
        Application $application,
        AssignApplicationAction $action,
    ): RedirectResponse {
        $this->authorize('assign', $application);

        $action->handle(
            $application,
            User::query()->findOrFail((int) $request->validated('staff_id')),
            $request->user(),
            $request->validated('note'),
        );

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã phân công hồ sơ cho cán bộ xử lý.');
    }

    public function claim(Request $request, Application $application, ClaimApplicationAction $action): RedirectResponse
    {
        $this->authorize('claim', $application);

        $action->handle($application, $request->user());

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã nhận hồ sơ.');
    }

    public function startProcessing(Request $request, Application $application, StartProcessingAction $action): RedirectResponse
    {
        $this->authorize('startProcessing', $application);

        $action->handle($application, $request->user());

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã bắt đầu xử lý hồ sơ.');
    }

    public function requestSupplement(
        RequestSupplementRequest $request,
        Application $application,
        RequestSupplementAction $action,
    ): RedirectResponse {
        $this->authorize('requestSupplement', $application);

        $action->handle($application, $request->user(), $request->validated('note'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã yêu cầu bổ sung tài liệu.');
    }

    public function resume(Request $request, Application $application, ResumeProcessingAction $action): RedirectResponse
    {
        $this->authorize('resume', $application);

        $action->handle($application, $request->user());

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã tiếp tục xử lý hồ sơ.');
    }

    public function approve(
        ApproveApplicationRequest $request,
        Application $application,
        ApproveApplicationAction $action,
    ): RedirectResponse {
        $this->authorize('approve', $application);

        $action->handle($application, $request->user(), $request->validated('result_note'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã duyệt hồ sơ.');
    }

    public function reject(
        RejectApplicationRequest $request,
        Application $application,
        RejectApplicationAction $action,
    ): RedirectResponse {
        $this->authorize('reject', $application);

        $action->handle($application, $request->user(), $request->validated('rejection_reason'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã từ chối hồ sơ.');
    }

    public function submitForApproval(
        SubmitForApprovalRequest $request,
        Application $application,
        SubmitForApprovalAction $action,
    ): RedirectResponse {
        $this->authorize('submitForApproval', $application);

        $action->handle($application, $request->user(), $request->validated('note'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã gửi hồ sơ chờ duyệt.');
    }

    public function returnToProcessing(
        ReturnToProcessingRequest $request,
        Application $application,
        ReturnToProcessingAction $action,
    ): RedirectResponse {
        $this->authorize('returnToProcessing', $application);

        $action->handle($application, $request->user(), $request->validated('note'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã trả hồ sơ về xử lý lại.');
    }

    public function storeResultDocument(
        StoreResultDocumentRequest $request,
        Application $application,
        StoreResultDocumentAction $action,
    ): RedirectResponse {
        $this->authorize('uploadResultDocument', $application);

        $action->handle(
            $application,
            $request->user(),
            $request->file('document'),
            $request->validated('requirement_code'),
        );

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã đính kèm tài liệu kết quả.');
    }

    public function downloadDocument(
        Request $request,
        Application $application,
        ApplicationDocument $document,
    ): StreamedResponse {
        $this->authorize('view', $application);
        abort_unless($document->application_id === $application->getKey(), 404);

        $this->authorize('download', $document);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $disk = Storage::disk($document->disk);

        if ($request->boolean('inline')) {
            return $disk->response($document->path, $document->original_name, [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            ]);
        }

        return $disk->download($document->path, $document->original_name);
    }
}
