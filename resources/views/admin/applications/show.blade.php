@extends('admin.layouts.app')

@section('title', 'Chi tiết hồ sơ '.$application->application_code)

@section('content')
    @php
        $citizen = $application->historicalCitizen;
        $service = $application->historicalServiceType;
        $department = $service?->historicalResponsibleDepartment;
        $assignedStaff = $application->historicalAssignedStaff;
    @endphp

    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.applications.index') }}">
                ← Danh sách hồ sơ
            </a>
            <h1 class="mt-1 text-2xl font-bold text-gray-950">{{ $application->application_code }}</h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                <span>{{ $service?->name ?: 'Dịch vụ không còn khả dụng' }}</span>
                @if ($service?->trashed())
                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                @elseif ($service && ! $service->is_active)
                    <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                @endif
            </div>
        </div>

        <x-admin.badge :variant="$application->status->badgeVariant()">
            {{ $application->status->label() }}
        </x-admin.badge>
    </div>

    @if ($application->isOverdue())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            Hồ sơ đã quá hạn xử lý theo {{ $service?->processing_time_days }} ngày kể từ khi nộp.
        </div>
    @endif

    @if ($application->status->value === 'pending_approval')
        <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            Hồ sơ đang <span class="font-semibold">chờ quản lý duyệt</span> — Staff đã gửi kết quả/đề xuất. Quản lý có thể duyệt, từ chối hoặc trả về xử lý lại.
        </div>
    @endif

    @if ($application->status->value === 'supplement_required')
        @php
            $missingDocs = $application->missingRequiredDocuments();
        @endphp
        <div class="mb-5 overflow-hidden rounded-xl border border-amber-300 bg-amber-50" role="alert">
            <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <p class="text-[13px] font-bold uppercase tracking-wide text-amber-800">Đang chờ bổ sung tài liệu</p>
                    @if ($application->supplementNote())
                        <p class="mt-1 break-words text-sm text-amber-900 whitespace-pre-wrap">
                            <span class="font-semibold">Ghi chú của cán bộ:</span> {{ $application->supplementNote() }}
                        </p>
                    @endif
                    @if ($missingDocs !== [])
                        <p class="mt-2 text-sm text-amber-900">
                            <span class="font-semibold">Tài liệu còn thiếu:</span>
                            @foreach ($missingDocs as $missing)
                                <x-admin.badge variant="warning">{{ $missing['label'] }}</x-admin.badge>
                            @endforeach
                        </p>
                    @endif
                </div>
                @can('resume', $application)
                    <form method="POST" action="{{ route('admin.applications.resume', $application) }}" class="shrink-0">
                        @csrf
                        <x-admin.button type="submit" class="whitespace-nowrap">Tiếp tục xử lý</x-admin.button>
                    </form>
                @endcan
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="admin-card" aria-labelledby="application-info-title">
                <h2 id="application-info-title" class="admin-card-title">Thông tin hồ sơ</h2>
                <div class="admin-card-body">
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div class="min-w-0">
                            <dt class="text-[13px] font-medium text-gray-500">Mã hồ sơ</dt>
                            <dd class="mt-0.5 break-words font-medium text-gray-950">{{ $application->application_code }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[13px] font-medium text-gray-500">Trạng thái</dt>
                            <dd class="mt-0.5 break-words">{{ $application->status->label() }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[13px] font-medium text-gray-500">Công dân</dt>
                            <dd class="mt-0.5 flex flex-wrap items-center gap-2 break-words font-medium text-gray-950">
                                <span class="break-words">{{ $citizen?->name ?: 'Không còn thông tin' }}</span>
                                @if ($citizen?->trashed())
                                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                @elseif ($citizen && ! $citizen->is_active)
                                    <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                                @endif
                            </dd>
                            @if ($citizen?->citizen_id)
                                <dd class="mt-1 break-words text-xs text-gray-500">Mã định danh: {{ $citizen->citizen_id }}</dd>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[13px] font-medium text-gray-500">Cán bộ đang xử lý</dt>
                            <dd class="mt-0.5 flex flex-wrap items-center gap-2 break-words font-medium text-gray-950">
                                <span class="break-words">{{ $assignedStaff?->name ?: 'Chưa phân công' }}</span>
                                @if ($assignedStaff?->trashed())
                                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                @elseif ($assignedStaff && ! $assignedStaff->is_active)
                                    <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                                @endif
                            </dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[13px] font-medium text-gray-500">Dịch vụ</dt>
                            <dd class="mt-0.5 break-words font-medium text-gray-950">{{ $service?->name ?: 'Không còn thông tin' }}</dd>
                            @if ($service?->code)
                                <dd class="mt-1 break-words text-xs text-gray-500">{{ $service->code }}</dd>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[13px] font-medium text-gray-500">Phòng ban phụ trách</dt>
                            <dd class="mt-0.5 flex flex-wrap items-center gap-2 break-words font-medium text-gray-950">
                                <span class="break-words">{{ $department?->name ?: 'Không còn thông tin' }}</span>
                                @if ($department?->trashed())
                                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                @endif
                            </dd>
                            @if ($department?->code)
                                <dd class="mt-1 break-words text-xs text-gray-500">{{ $department->code }}</dd>
                            @endif
                        </div>
                    </dl>
                </div>
            </section>

            <section class="admin-card" aria-labelledby="submitted-data-title">
                <h2 id="submitted-data-title" class="admin-card-title">Dữ liệu đã khai</h2>
                <div class="admin-card-body">
                    @if (filled($application->form_data))
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($application->form_data as $key => $value)
                                <div class="rounded-lg border border-border bg-gray-50 px-3 py-2">
                                    <dt class="text-[13px] font-medium text-gray-500">{{ str_replace('_', ' ', (string) $key) }}</dt>
                                    <dd class="mt-1 break-words text-sm font-medium text-gray-900">
                                        {{ is_scalar($value) || $value === null
                                            ? ($value ?? '—')
                                            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-gray-600">Hồ sơ không có dữ liệu biểu mẫu.</p>
                    @endif
                </div>
            </section>

            @if ($application->result_note || $application->rejection_reason)
                <section class="admin-card" aria-labelledby="application-result-title">
                    <h2 id="application-result-title" class="admin-card-title">Kết quả xử lý</h2>
                    <div class="admin-card-body space-y-3">
                        @if ($application->result_note)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <p class="text-[13px] font-semibold text-emerald-800">Ghi chú kết quả</p>
                                <p class="mt-1 break-words text-sm text-emerald-900 whitespace-pre-wrap">{{ $application->result_note }}</p>
                            </div>
                        @endif
                        @if ($application->rejection_reason)
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                <p class="text-[13px] font-semibold text-red-800">Lý do từ chối</p>
                                <p class="mt-1 break-words text-sm text-red-900 whitespace-pre-wrap">{{ $application->rejection_reason }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6" aria-label="Thông tin bổ sung và thao tác xử lý">
            <section class="admin-card" aria-labelledby="timestamps-title">
                <h2 id="timestamps-title" class="admin-card-title">Các mốc thời gian</h2>
                <dl class="admin-card-body space-y-3 text-sm">
                    <div class="min-w-0">
                        <dt class="text-gray-500">Ngày nộp</dt>
                        <dd class="break-words font-medium text-gray-950">{{ $application->submitted_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-gray-500">Bắt đầu xử lý</dt>
                        <dd class="break-words font-medium text-gray-950">{{ $application->processing_started_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-gray-500">Hoàn tất</dt>
                        <dd class="break-words font-medium text-gray-950">{{ $application->completed_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-gray-500">Tạo bản ghi</dt>
                        <dd class="break-words font-medium text-gray-950">{{ $application->created_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-gray-500">Cập nhật gần nhất</dt>
                        <dd class="break-words font-medium text-gray-950">{{ $application->updated_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-card" aria-labelledby="workflow-actions-title">
                <h2 id="workflow-actions-title" class="admin-card-title">Thao tác</h2>
                <div class="admin-card-body space-y-3">
                    @php
                        $status = $application->status->value;
                        $assignedToMe = $application->assignedStaff?->getKey() === auth()->id();
                        $unassigned = $application->assignedStaff === null;
                        $isTerminal = in_array($status, ['approved', 'rejected'], true);

                        $guidance = match ($status) {
                            'received' => $assignedToMe
                                ? 'Hồ sơ đã được gán cho bạn. Hãy bắt đầu xử lý để chuyển sang bước tiếp theo.'
                                : ($unassigned
                                    ? 'Hồ sơ chưa có người phụ trách. Bạn có thể nhận xử lý nếu thuộc phòng ban phụ trách dịch vụ.'
                                    : 'Hồ sơ đã được gán cho cán bộ khác. Bạn không thể thao tác hồ sơ này.'),
                            'assigned' => $assignedToMe
                                ? 'Hồ sơ đã được phân công cho bạn. Hãy bắt đầu xử lý.'
                                : 'Hồ sơ đã được phân công cho cán bộ khác.',
                            'processing' => $assignedToMe
                                ? 'Bạn đang xử lý hồ sơ. Có thể yêu cầu bổ sung hoặc gửi kết quả chờ quản lý duyệt.'
                                : 'Hồ sơ đang được cán bộ khác xử lý.',
                            'supplement_required' => 'Đang chờ công dân nộp tài liệu bổ sung. Khi đã đủ tài liệu, hãy tiếp tục xử lý.',
                            'pending_approval' => auth()->user()?->isManager() || auth()->user()?->isSuperAdmin()
                                ? 'Hồ sơ đang chờ bạn duyệt. Có thể duyệt, từ chối hoặc trả về xử lý lại.'
                                : 'Hồ sơ đã gửi chờ quản lý duyệt.',
                            'approved' => 'Hồ sơ đã được duyệt và hoàn tất.',
                            'rejected' => 'Hồ sơ đã bị từ chối và hoàn tất.',
                            default => null,
                        };
                    @endphp
                    @if ($guidance)
                        <div class="rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3 text-sm leading-5 text-blue-900" role="status">
                            <p class="text-[13px] font-bold uppercase tracking-wide text-blue-700">Bước tiếp theo</p>
                            <p class="mt-1 break-words">{{ $guidance }}</p>
                        </div>
                    @endif

                    @can('claim', $application)
                        @if ($status === 'received' && $unassigned)
                            <form method="POST" action="{{ route('admin.applications.claim', $application) }}">
                                @csrf
                                <x-admin.button type="submit" class="w-full">Nhận hồ sơ</x-admin.button>
                            </form>
                        @endif
                    @endcan

                    @can('assign', $application)
                        @if (! $isTerminal)
                            <x-admin.button type="button" data-dialog-open="assign-application-{{ $application->id }}" class="w-full">
                                Phân công / đổi cán bộ
                            </x-admin.button>
                        @endif
                    @endcan

                    @can('startProcessing', $application)
                        @if (in_array($status, ['received', 'assigned'], true) && $assignedToMe)
                            <form method="POST" action="{{ route('admin.applications.start-processing', $application) }}">
                                @csrf
                                <x-admin.button type="submit" class="w-full">Bắt đầu xử lý</x-admin.button>
                            </form>
                        @endif
                    @endcan

                    @can('requestSupplement', $application)
                        @if ($status === 'processing' && $assignedToMe)
                            <x-admin.button type="button" variant="secondary" data-dialog-open="request-supplement-{{ $application->id }}" class="w-full">
                                Yêu cầu bổ sung
                            </x-admin.button>
                        @endif
                    @endcan

                    @can('resume', $application)
                        @if ($status === 'supplement_required' && $assignedToMe)
                            <form method="POST" action="{{ route('admin.applications.resume', $application) }}">
                                @csrf
                                <x-admin.button type="submit" class="w-full">Tiếp tục xử lý</x-admin.button>
                            </form>
                        @endif
                    @endcan

                    @can('submitForApproval', $application)
                        @if ($status === 'processing' && $assignedToMe)
                            <x-admin.button type="button" data-dialog-open="submit-for-approval-{{ $application->id }}" class="w-full">
                                Gửi duyệt
                            </x-admin.button>
                        @endif
                    @endcan

                    @can('returnToProcessing', $application)
                        @if ($status === 'pending_approval')
                            <x-admin.button type="button" variant="secondary" data-dialog-open="return-application-{{ $application->id }}" class="w-full">
                                Trả về xử lý lại
                            </x-admin.button>
                        @endif
                    @endcan

                    @can('approve', $application)
                        @if ($status === 'pending_approval')
                            <x-admin.button type="button" variant="secondary" data-dialog-open="approve-application-{{ $application->id }}" class="w-full">
                                Duyệt hồ sơ
                            </x-admin.button>
                        @endif
                    @endcan

                    @can('reject', $application)
                        @if ($status === 'pending_approval')
                            <x-admin.button type="button" variant="danger" data-dialog-open="reject-application-{{ $application->id }}" class="w-full">
                                Từ chối hồ sơ
                            </x-admin.button>
                        @endif
                    @endcan

                    @can('uploadResultDocument', $application)
                        @if ($status === 'processing' && $assignedToMe)
                            <x-admin.button type="button" variant="secondary" data-dialog-open="result-document-{{ $application->id }}" class="w-full">
                                Đính kèm tài liệu kết quả
                            </x-admin.button>
                        @endif
                    @endcan
                </div>
            </section>
        </aside>
    </div>

    <section class="admin-card mt-6" aria-labelledby="documents-title">
        <h2 id="documents-title" class="admin-card-title">Tài liệu hồ sơ</h2>
        <div class="admin-card-body">
            <div class="grid gap-3 md:grid-cols-2">
                @forelse ($application->documents as $document)
                    <article class="rounded-lg border border-border bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-words text-sm font-semibold text-gray-950">{{ $document->original_name }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $document->requirement_label ?: match ($document->document_kind?->value) {
                                        'submission' => 'Tài liệu nộp ban đầu',
                                        'supplement' => 'Tài liệu bổ sung',
                                        'result' => 'Tài liệu kết quả',
                                        default => 'Tài liệu hồ sơ',
                                    } }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @php
                                    $previewable = in_array($document->mime_type, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true);
                                @endphp
                                @if ($previewable)
                                    <button type="button" class="text-sm font-semibold text-primary hover:underline" data-dialog-open="preview-document-{{ $application->id }}-{{ $document->id }}">Xem</button>
                                @endif
                                @can('download', $document)
                                    <a class="text-sm font-semibold text-primary hover:underline"
                                       href="{{ route('admin.applications.documents.download', [$application, $document]) }}">
                                        Tải xuống
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                            <span>{{ $document->mime_type ?: 'Không rõ định dạng' }}</span>
                            @if ($document->file_size)
                                <span>{{ number_format($document->file_size / 1024, 1) }} KB</span>
                            @endif
                            <span>{{ $document->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($document->uploader)
                            <p class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                <span>Tải lên bởi {{ $document->uploader->name }}</span>
                                @if ($document->uploader->trashed())
                                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                @elseif (! $document->uploader->is_active)
                                    <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                                @endif
                            </p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-gray-600">Chưa có tài liệu.</p>
                @endforelse
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="admin-card" aria-labelledby="status-history-title">
            <h2 id="status-history-title" class="admin-card-title">Lịch sử trạng thái</h2>
            <ol class="admin-card-body space-y-4">
                @forelse ($application->statusHistories as $history)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $history->from_status?->label() ?: 'Khởi tạo' }}
                                → {{ $history->to_status->label() }}
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>{{ $history->created_at?->format('d/m/Y H:i') }}</span>
                                @if ($history->changedBy)
                                    <span>bởi {{ $history->changedBy->name }}</span>
                                    @if ($history->changedBy->trashed())
                                        <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                    @elseif (! $history->changedBy->is_active)
                                        <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                                    @endif
                                @endif
                            </p>
                            @if ($history->note)
                                <p class="mt-1 break-words text-sm text-gray-600 whitespace-pre-wrap">{{ $history->note }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-600">Chưa có lịch sử trạng thái.</li>
                @endforelse
            </ol>
            </div>
        </section>

        <section class="admin-card" aria-labelledby="assignments-title">
            <div class="admin-card-body">
                <h2 id="assignments-title" class="text-lg font-bold text-gray-950">Lịch sử phân công</h2>
            <ol class="mt-4 space-y-3">
                @forelse ($application->assignments as $assignment)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-900">
                                <span>{{ $assignment->staff?->name ?: 'Cán bộ không còn thông tin' }}</span>
                                @if ($assignment->staff?->trashed())
                                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                @elseif ($assignment->staff && ! $assignment->staff->is_active)
                                    <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                                @endif
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>{{ $assignment->assigned_at?->format('d/m/Y H:i') }}</span>
                                @if ($assignment->assignedBy)
                                    <span>bởi {{ $assignment->assignedBy->name }}</span>
                                    @if ($assignment->assignedBy->trashed())
                                        <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                    @elseif (! $assignment->assignedBy->is_active)
                                        <x-admin.badge variant="warning">Đã vô hiệu hóa</x-admin.badge>
                                    @endif
                                @endif
                            </p>
                            @if ($assignment->department)
                                <p class="mt-1 flex flex-wrap items-center gap-2 break-words text-xs text-gray-500">
                                    <span class="break-words">{{ $assignment->department->name }}</span>
                                    @if ($assignment->department->trashed())
                                        <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                    @endif
                                </p>
                            @endif
                            @if ($assignment->note)
                                <p class="mt-1 break-words text-sm text-gray-600 whitespace-pre-wrap">{{ $assignment->note }}</p>
                            @endif
                            <p class="mt-1 break-words text-xs text-gray-500">
                                {{ $assignment->ended_at
                                    ? 'Kết thúc '.$assignment->ended_at->format('d/m/Y H:i')
                                    : 'Đang hiệu lực' }}
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-600">Chưa có lịch sử phân công.</li>
                @endforelse
            </ol>
            </div>
        </section>
    </div>

    @can('assign', $application)
        @if (! in_array($application->status->value, ['approved', 'rejected'], true))
            <x-admin.dialog
                id="assign-application-{{ $application->id }}"
                title="Phân công cán bộ xử lý"
                description="Chọn staff đang hoạt động thuộc phòng ban phụ trách dịch vụ của hồ sơ."
                data-open-on-error="{{ $errors->has('staff_id') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.assign', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="assign-staff-{{ $application->id }}">Cán bộ xử lý</label>
                    <select id="assign-staff-{{ $application->id }}" class="admin-select" name="staff_id" required>
                        <option value="">Chọn cán bộ…</option>
                        @foreach ($application->serviceType?->responsibleDepartment?->users?->filter(fn ($user) => $user->isStaff() && $user->canAccessProtectedResources()) ?? [] as $candidate)
                            <option value="{{ $candidate->id }}" @selected(old('staff_id') == $candidate->id)>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    @error('staff_id')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="assign-note-{{ $application->id }}">Ghi chú / Lý do đổi người xử lý</label>
                    <textarea id="assign-note-{{ $application->id }}" class="admin-input" name="note" rows="2" maxlength="1000" placeholder="Bắt buộc khi hồ sơ đã được nhận/đang xử lý hoặc chờ duyệt">{{ old('note') }}</textarea>
                    @error('note')<p class="admin-field-error">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-gray-500">Nếu hồ sơ chưa nhận, có thể để trống. Đã nhận/đang xử lý/chờ duyệt thì bắt buộc nhập lý do.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Phân công</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @can('requestSupplement', $application)
        @if ($application->status->value === 'processing')
            <x-admin.dialog
                id="request-supplement-{{ $application->id }}"
                title="Yêu cầu bổ sung tài liệu"
                description="Hồ sơ sẽ chuyển sang trạng thái chờ bổ sung; công dân sẽ thấy lý do và các tài liệu còn thiếu."
                data-open-on-error="{{ $errors->has('note') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.request-supplement', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="supplement-note-{{ $application->id }}">Lý do yêu cầu bổ sung</label>
                    <textarea id="supplement-note-{{ $application->id }}" class="admin-input" name="note" rows="3" maxlength="2000" required>{{ old('note') }}</textarea>
                    @error('note')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Gửi yêu cầu</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @can('submitForApproval', $application)
        @if ($application->status->value === 'processing')
            <x-admin.dialog
                id="submit-for-approval-{{ $application->id }}"
                title="Gửi duyệt"
                description="Hồ sơ sẽ chuyển sang chờ quản lý duyệt (có thể duyệt, từ chối hoặc trả về)."
                data-open-on-error="{{ $errors->has('note') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.submit-for-approval', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="submit-note-{{ $application->id }}">Ghi chú gửi duyệt (tùy chọn)</label>
                    <textarea id="submit-note-{{ $application->id }}" class="admin-input" name="note" rows="3" maxlength="2000">{{ old('note') }}</textarea>
                    @error('note')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Gửi duyệt</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @can('returnToProcessing', $application)
        @if ($application->status->value === 'pending_approval')
            <x-admin.dialog
                id="return-application-{{ $application->id }}"
                title="Trả về xử lý lại"
                description="Hồ sơ sẽ quay lại trạng thái đang xử lý để cán bộ tiếp tục xử lý."
                data-open-on-error="{{ $errors->has('note') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.return', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="return-note-{{ $application->id }}">Lý do trả về</label>
                    <textarea id="return-note-{{ $application->id }}" class="admin-input" name="note" rows="3" maxlength="2000" required>{{ old('note') }}</textarea>
                    @error('note')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit" variant="secondary">Xác nhận trả về</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @can('approve', $application)
        @if ($application->status->value === 'pending_approval')
            <x-admin.dialog
                id="approve-application-{{ $application->id }}"
                title="Duyệt hồ sơ"
                description="Hồ sơ sẽ được duyệt và chuyển sang trạng thái đã hoàn thành."
                data-open-on-error="{{ $errors->has('result_note') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="result-note-{{ $application->id }}">Ghi chú kết quả (tùy chọn)</label>
                    <textarea id="result-note-{{ $application->id }}" class="admin-input" name="result_note" rows="3" maxlength="2000">{{ old('result_note') }}</textarea>
                    @error('result_note')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Xác nhận duyệt</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @can('reject', $application)
        @if ($application->status->value === 'pending_approval')
            <x-admin.dialog
                id="reject-application-{{ $application->id }}"
                title="Từ chối hồ sơ"
                description="Hồ sơ sẽ bị từ chối kèm lý do bắt buộc; không thể đính kèm tài liệu kết quả."
                data-open-on-error="{{ $errors->has('rejection_reason') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="rejection-reason-{{ $application->id }}">Lý do từ chối</label>
                    <textarea id="rejection-reason-{{ $application->id }}" class="admin-input" name="rejection_reason" rows="3" maxlength="2000" required>{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit" variant="danger">Xác nhận từ chối</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @can('uploadResultDocument', $application)
        @if ($application->status->value === 'processing')
            <x-admin.dialog
                id="result-document-{{ $application->id }}"
                title="Đính kèm tài liệu kết quả"
                description="Tài liệu kết quả chỉ gắn khi hồ sơ đang xử lý (trước hoặc trong lúc duyệt)."
                data-open-on-error="{{ $errors->has('document') ? 'true' : 'false' }}"
            >
            <form method="POST" action="{{ route('admin.applications.result-documents.store', $application) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="result-file-{{ $application->id }}">Tài liệu kết quả</label>
                    <input id="result-file-{{ $application->id }}" class="admin-input" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                    @error('document')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Đính kèm</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
        @endif
    @endcan

    @foreach ($application->documents as $document)
        @if (in_array($document->mime_type, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true))
            <x-admin.dialog
                id="preview-document-{{ $application->id }}-{{ $document->id }}"
                title="Xem trước tài liệu"
                description="{{ $document->original_name }}"
            >
                <div class="space-y-3">
                    <iframe
                        class="h-[70vh] w-full rounded-lg border border-border bg-gray-100"
                        data-src="{{ route('admin.applications.documents.download', [$application, $document]) }}?inline=1"
                        title="{{ $document->original_name }}"
                    ></iframe>
                    <div class="flex justify-end gap-2 border-t border-border pt-4">
                        <x-admin.button variant="secondary" data-dialog-close>Đóng</x-admin.button>
                        <x-admin.button :href="route('admin.applications.documents.download', [$application, $document])">Tải xuống</x-admin.button>
                    </div>
                </div>
            </x-admin.dialog>
        @endif
    @endforeach
@endsection
