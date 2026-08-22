@extends('admin.layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-950">Nhật ký hoạt động</h1>
        <p class="mt-1 text-sm text-gray-600">Tra cứu các thao tác bảo mật, quản trị và xử lý hồ sơ đã được hệ thống ghi nhận.</p>
    </div>

    <section class="admin-card" aria-labelledby="activity-log-results-title">
        <h2 id="activity-log-results-title" class="sr-only">Kết quả tra cứu nhật ký</h2>

        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="border-b border-border p-4 sm:p-5">
            <div class="grid gap-4 md:grid-cols-2 md:items-end">
                <div>
                    <label class="admin-label" for="activity-q">Tìm kiếm</label>
                    <input
                        id="activity-q"
                        class="admin-input"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        maxlength="100"
                        placeholder="Hành động, mô tả, actor, mã hồ sơ"
                    >
                    @error('q') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label" for="activity-actor">Người thực hiện</label>
                    <select id="activity-actor" class="admin-select" name="actor_id">
                        <option value="">Tất cả người thực hiện</option>
                        @foreach ($actorOptions as $actor)
                            <option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>
                                {{ $actor->name }} - {{ $actor->email }} ({{ $actor->role->label() }})
                            </option>
                        @endforeach
                    </select>
                    @error('actor_id') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label" for="activity-action">Hành động</label>
                    <select id="activity-action" class="admin-select" name="action">
                        <option value="">Tất cả hành động</option>
                        @foreach ($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['action'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('action') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label" for="activity-subject">Đối tượng</label>
                    <select id="activity-subject" class="admin-select" name="subject_type">
                        <option value="">Tất cả đối tượng</option>
                        @foreach ($subjectTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['subject_type'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('subject_type') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label" for="activity-date-from">Từ ngày</label>
                    <input id="activity-date-from" class="admin-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                    @error('date_from') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label" for="activity-date-to">Đến ngày</label>
                    <input id="activity-date-to" class="admin-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    @error('date_to') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-2 md:col-span-2">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if ($hasFilters)
                        <x-admin.button variant="secondary" :href="route('admin.activity-logs.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>

        @if ($activityLogs->isEmpty())
            <div class="px-5 py-14 text-center">
                <h3 class="text-base font-semibold text-gray-950">Không tìm thấy nhật ký phù hợp</h3>
                <p class="mt-1 text-sm text-gray-600">Hãy kiểm tra từ khóa hoặc thay đổi bộ lọc.</p>
                @if ($hasFilters)
                    <x-admin.button class="mt-4" variant="secondary" :href="route('admin.activity-logs.index')">Xóa bộ lọc</x-admin.button>
                @endif
            </div>
        @else
            <div class="admin-table-wrap overflow-x-auto rounded-none border-x-0 border-t-0">
                <table class="admin-table min-w-[900px] w-full">
                    <caption class="sr-only">Danh sách nhật ký hoạt động</caption>
                    <thead>
                        <tr>
                            <th scope="col">Thời gian</th>
                            <th scope="col">Người thực hiện</th>
                            <th scope="col">Hành động</th>
                            <th scope="col">Đối tượng</th>
                            <th scope="col"><span class="sr-only">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activityLogs as $log)
                            @php
                                $metadata = $log->metadata ?? [];
                                $application = $metadata['application'] ?? null;
                                $department = $metadata['department'] ?? null;
                                $subjectTypeLabel = $subjectTypeOptions[$log->subject_type] ?? 'Đối tượng khác';
                                $subjectLabel = $application['application_code'] ?? $department['name'] ?? $subjectTypeLabel;
                                $actionLabel = $actionOptions[$log->action] ?? $log->action;
                                $detailId = 'activity-log-detail-'.$log->id;
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="max-w-[220px]">
                                    @if ($log->actor)
                                        <p class="truncate font-semibold text-gray-950" title="{{ $log->actor->name }}">{{ $log->actor->name }}</p>
                                        <p class="truncate text-xs text-gray-500" title="{{ $log->actor->email }}">{{ $log->actor->email }}</p>
                                        <x-admin.badge :variant="$log->actor->role->badgeVariant()">{{ $log->actor->role->label() }}</x-admin.badge>
                                    @else
                                        <span class="text-sm text-gray-500">Hệ thống</span>
                                    @endif
                                </td>
                                <td class="max-w-[300px]">
                                    <p class="font-semibold text-gray-950">{{ $actionLabel }}</p>
                                    <p class="line-clamp-1 text-xs text-gray-500">{{ $log->description ?: $log->action }}</p>
                                    @if (($metadata['note_present'] ?? false) === true)
                                        <p class="mt-1 text-xs font-medium text-amber-700">Có ghi chú nghiệp vụ</p>
                                    @endif
                                </td>
                                <td class="max-w-[180px]">
                                    <p class="truncate font-semibold text-gray-950" title="{{ $subjectLabel }}">{{ $subjectLabel }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $log->subject_type }}">{{ $subjectTypeLabel }} #{{ $log->subject_id }}</p>
                                    @if (isset($application['status_label']))
                                        <p class="mt-1 text-xs text-gray-500">{{ $application['status_label'] }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    <x-admin.button type="button" variant="ghost" class="min-h-9 whitespace-nowrap px-3 py-2 text-xs" data-dialog-open="{{ $detailId }}">Chi tiết</x-admin.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($activityLogs->hasPages())
                <div class="border-t border-border px-4 py-4 sm:px-5">
                    {{ $activityLogs->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </section>

    @foreach ($activityLogs as $log)
        @php
            $metadata = $log->metadata ?? [];
            $application = $metadata['application'] ?? null;
            $department = $metadata['department'] ?? null;
            $actorSnapshot = $metadata['actor'] ?? null;
            $assignedStaff = $metadata['assigned_staff'] ?? null;
            $document = $metadata['document'] ?? null;
            $detailId = 'activity-log-detail-'.$log->id;
            $actionLabel = $actionOptions[$log->action] ?? $log->action;
        @endphp

        <x-admin.dialog :id="$detailId" title="Chi tiết nhật ký" :description="$actionLabel">
            <div class="space-y-5 text-sm">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">Thời gian</dt>
                        <dd class="mt-1 text-gray-950">{{ $log->created_at?->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">Hành động</dt>
                        <dd class="mt-1 font-semibold text-gray-950">{{ $actionLabel }}</dd>
                        <dd class="mt-1 break-all font-mono text-xs text-gray-500">{{ $log->action }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">IP</dt>
                        <dd class="mt-1 text-gray-950">{{ $log->ip_address ?: 'Không ghi nhận' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">Đối tượng</dt>
                        <dd class="mt-1 text-gray-950">{{ $subjectTypeOptions[$log->subject_type] ?? 'Đối tượng khác' }} #{{ $log->subject_id }}</dd>
                    </div>
                </dl>

                <div>
                    <h3 class="text-xs font-semibold uppercase text-gray-500">Mô tả</h3>
                    <p class="mt-1 rounded-lg bg-gray-50 px-3 py-2 text-gray-800">{{ $log->description ?: 'Không có mô tả.' }}</p>
                </div>

                @if ($actorSnapshot)
                    <div>
                        <h3 class="text-xs font-semibold uppercase text-gray-500">Người thực hiện</h3>
                        <dl class="mt-2 grid gap-2 rounded-lg border border-border px-3 py-2 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500">Tên</dt>
                                <dd class="font-medium text-gray-950">{{ $actorSnapshot['name'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Email</dt>
                                <dd class="break-all text-gray-700">{{ $actorSnapshot['email'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Vai trò</dt>
                                <dd class="font-medium text-gray-700">
                                    {{ isset($actorSnapshot['role']) ? (\App\Enums\UserRole::tryFrom($actorSnapshot['role'])?->label() ?? 'Không ghi nhận') : 'Không ghi nhận' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endif

                @if ($application)
                    <div>
                        <h3 class="text-xs font-semibold uppercase text-gray-500">Hồ sơ</h3>
                        <dl class="mt-2 grid gap-2 rounded-lg border border-border px-3 py-2 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500">Mã hồ sơ</dt>
                                <dd class="font-mono font-semibold text-primary">{{ $application['application_code'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Trạng thái</dt>
                                <dd class="font-medium text-gray-950">{{ $application['status_label'] ?? $application['status'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Công dân</dt>
                                <dd class="text-gray-700">{{ $application['citizen']['name'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Dịch vụ</dt>
                                <dd class="text-gray-700">{{ $application['service_type']['name'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Phòng ban</dt>
                                <dd class="text-gray-700">{{ $application['department']['name'] ?? 'Không ghi nhận' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Cán bộ phụ trách</dt>
                                <dd class="text-gray-700">{{ $application['assigned_staff']['name'] ?? 'Chưa phân công' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif

                @if ($department)
                    <div>
                        <h3 class="text-xs font-semibold uppercase text-gray-500">Phòng ban</h3>
                        <p class="mt-1 rounded-lg border border-border px-3 py-2 text-gray-800">{{ $department['name'] ?? 'Không ghi nhận' }} {{ isset($department['code']) ? '('.$department['code'].')' : '' }}</p>
                    </div>
                @endif

                @if ($assignedStaff || $document || isset($metadata['from_status_label'], $metadata['to_status_label']) || ($metadata['note_present'] ?? false))
                    <div>
                        <h3 class="text-xs font-semibold uppercase text-gray-500">Thông tin nghiệp vụ</h3>
                        <dl class="mt-2 grid gap-2 rounded-lg border border-border px-3 py-2 sm:grid-cols-2">
                            @if ($assignedStaff)
                                <div>
                                    <dt class="text-xs text-gray-500">Cán bộ được gán</dt>
                                    <dd class="font-medium text-gray-950">{{ $assignedStaff['name'] ?? 'Không ghi nhận' }}</dd>
                                </div>
                            @endif
                            @if (isset($metadata['from_status_label'], $metadata['to_status_label']))
                                <div>
                                    <dt class="text-xs text-gray-500">Chuyển trạng thái</dt>
                                    <dd class="font-medium text-gray-950">{{ $metadata['from_status_label'] }} -> {{ $metadata['to_status_label'] }}</dd>
                                </div>
                            @endif
                            @if (($metadata['note_present'] ?? false) === true)
                                <div>
                                    <dt class="text-xs text-gray-500">Ghi chú nghiệp vụ</dt>
                                    <dd class="font-medium text-amber-700">Có ghi chú</dd>
                                </div>
                            @endif
                            @if ($document)
                                <div>
                                    <dt class="text-xs text-gray-500">Tài liệu</dt>
                                    <dd class="font-medium text-gray-950">{{ $document['original_name'] ?? 'Không ghi nhận' }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <x-admin.button type="button" variant="secondary" data-dialog-close>Đóng</x-admin.button>
            </x-slot:footer>
        </x-admin.dialog>
    @endforeach
@endsection
