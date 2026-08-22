@extends('admin.layouts.app')

@section('title', 'Hồ sơ dịch vụ công')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Hồ sơ dịch vụ công</h1>
            <p class="mt-1 text-sm text-gray-600">Tra cứu hồ sơ trong đúng phạm vi trách nhiệm của bạn.</p>
        </div>
        <div>
            <x-admin.button variant="secondary" :href="route('admin.export', array_merge(['resource' => 'applications'], request()->query()))">
                Xuất CSV
            </x-admin.button>
        </div>
    </div>

    @isset($stats)
        <section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2" aria-label="Thống kê hồ sơ">
            @foreach ([
                ['label' => 'Đang chờ xử lý', 'value' => $stats['pending'], 'class' => 'text-amber-700', 'hint' => 'received + processing + supplement_required'],
                ['label' => 'Quá hạn', 'value' => $stats['overdue'], 'class' => 'text-red-700', 'hint' => 'Chưa hoàn thành quá processing_time_days kể từ submitted_at'],
            ] as $stat)
                <article class="admin-card">
                    <div class="admin-card-body">
                        <p class="text-[13px] font-medium text-gray-500">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold {{ $stat['class'] }}">{{ number_format($stat['value']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $stat['hint'] }}</p>
                    </div>
                </article>
            @endforeach
        </section>
    @endisset

    @if ($claimable > 0)
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800" role="status">
            Có <strong>{{ number_format($claimable) }}</strong> hồ sơ chưa có người phụ trách trong phòng ban của bạn.
            Bạn có thể nhận xử lý từ luồng nghiệp vụ F05.
        </div>
        <section class="mb-5 admin-card" aria-labelledby="claimable-title" id="claimable">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-3 sm:px-5">
                <h2 id="claimable-title" class="text-base font-bold text-gray-950">Hồ sơ có thể nhận</h2>
                <x-admin.badge variant="info">{{ number_format($claimable) }} hồ sơ</x-admin.badge>
            </div>
            <div class="admin-card-body space-y-2">
                @forelse ($claimableApplications as $application)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border bg-white px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-semibold text-primary">{{ $application->application_code }}</p>
                            <p class="truncate text-xs text-gray-600">{{ $application->serviceType?->name }} · {{ $application->citizen?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $application->status->label() }} · {{ $application->serviceType?->responsibleDepartment?->name }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-admin.button variant="ghost" :href="route('admin.applications.show', $application)">Xem</x-admin.button>
                            <form method="POST" action="{{ route('admin.applications.claim', $application) }}">
                                @csrf
                                <x-admin.button type="submit">Nhận xử lý</x-admin.button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Không có hồ sơ nào đang chờ nhận.</p>
                @endforelse
            </div>
        </section>
    @endif

    <section class="admin-card" aria-labelledby="application-results-title">
        <h2 id="application-results-title" class="sr-only">Kết quả tra cứu hồ sơ</h2>

        <form method="GET" action="{{ route('admin.applications.index') }}" class="border-b border-border p-4 sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12 xl:items-end">
                <div class="xl:col-span-4">
                    <label class="admin-label" for="application-q">Tìm kiếm</label>
                    <input
                        id="application-q"
                        class="admin-input"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        maxlength="100"
                        placeholder="Mã hồ sơ, công dân, CCCD, dịch vụ"
                    >
                    @error('q') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="admin-label" for="application-status">Trạng thái</label>
                    <select id="application-status" class="admin-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option['value'] }}" @selected(($filters['status'] ?? null) === $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-3">
                    <label class="admin-label" for="application-service">Dịch vụ</label>
                    <select id="application-service" class="admin-select" name="service_type_id">
                        <option value="">Tất cả dịch vụ</option>
                        @foreach ($serviceOptions as $service)
                            <option value="{{ $service->id }}" @selected((string) ($filters['service_type_id'] ?? '') === (string) $service->id)>
                                {{ $service->name }} ({{ $service->code }})
                                @if ($service->trashed()) — Đã lưu trữ @elseif (! $service->is_active) — Ngừng hoạt động @endif
                            </option>
                        @endforeach
                    </select>
                    @error('service_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-3">
                    <label class="admin-label" for="application-department">Phòng ban</label>
                    <select id="application-department" class="admin-select" name="department_id">
                        <option value="">Tất cả phòng ban</option>
                        @foreach ($departmentOptions as $department)
                            <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>
                                {{ $department->name }} ({{ $department->code }})@if ($department->trashed()) — Đã lưu trữ @endif
                            </option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-3">
                    <label class="admin-label" for="application-staff">Người phụ trách</label>
                    <select id="application-staff" class="admin-select" name="assigned_staff_id">
                        <option value="">Tất cả cán bộ</option>
                        @foreach ($staffOptions as $staff)
                            <option value="{{ $staff->id }}" @selected((string) ($filters['assigned_staff_id'] ?? '') === (string) $staff->id)>
                                {{ $staff->name }}@if ($staff->trashed()) — Đã lưu trữ @elseif (! $staff->is_active) — Ngừng hoạt động @endif
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_staff_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="admin-label" for="application-submitted-from">Nộp từ ngày</label>
                    <input id="application-submitted-from" class="admin-input" type="date" name="submitted_from" value="{{ $filters['submitted_from'] ?? '' }}">
                    @error('submitted_from') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="admin-label" for="application-submitted-to">Nộp đến ngày</label>
                    <input id="application-submitted-to" class="admin-input" type="date" name="submitted_to" value="{{ $filters['submitted_to'] ?? '' }}">
                    @error('submitted_to') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="admin-label" for="application-overdue">Hạn xử lý</label>
                    <select id="application-overdue" class="admin-select" name="overdue">
                        <option value="">Tất cả</option>
                        <option value="1" @selected((bool) ($filters['overdue'] ?? false))>Chỉ hồ sơ quá hạn</option>
                    </select>
                    @error('overdue') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-3">
                    <label class="admin-label" for="application-sort">Sắp xếp</label>
                    <select id="application-sort" class="admin-select" name="sort">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('sort') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-2 xl:col-span-12">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if ($hasFilters)
                        <x-admin.button variant="secondary" :href="route('admin.applications.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>

        @if ($applications->isEmpty())
            <div class="px-5 py-12 text-center">
                @if ($authorizedApplicationCount === 0)
                    <h3 class="text-lg font-bold text-gray-950">Chưa có hồ sơ được gán cho bạn</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">
                        @if ($claimable > 0)
                            Bạn chưa có hồ sơ nào được gán, nhưng có <strong>{{ $claimable }}</strong> hồ sơ trong phòng ban đang chờ nhận ở trên. Hãy bấm “Nhận xử lý” để tiếp nhận.
                        @else
                            Hồ sơ được phân công hoặc thuộc phòng ban bạn phụ trách sẽ xuất hiện tại đây.
                        @endif
                    </p>
                @else
                    <h3 class="text-lg font-bold text-gray-950">Không tìm thấy hồ sơ phù hợp</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">Hãy thay đổi điều kiện tra cứu hoặc xóa bộ lọc để xem lại danh sách.</p>
                    <div class="mt-4">
                        <x-admin.button variant="secondary" :href="route('admin.applications.index')">Xóa bộ lọc</x-admin.button>
                    </div>
                @endif
            </div>
        @else
            <div class="border-b border-border px-4 pt-4 sm:px-5">
                <h2 class="text-base font-bold text-gray-950">Hồ sơ của tôi</h2>
                <p class="mt-0.5 text-sm text-gray-600">Các hồ sơ đã được gán cho bạn hoặc nằm trong phạm vi quản lý.</p>
            </div>
            <div class="admin-table-wrap overflow-x-auto rounded-none border-x-0 border-t-0" tabindex="0" aria-label="Bảng hồ sơ có thể cuộn ngang">
                <table class="admin-table min-w-[960px] w-full">
                    <caption class="sr-only">Danh sách hồ sơ trong phạm vi được phép</caption>
                    <thead>
                        <tr>
                            <th scope="col">Mã hồ sơ</th>
                            <th scope="col">Công dân</th>
                            <th scope="col">Dịch vụ</th>
                            <th scope="col">Phòng ban</th>
                            <th scope="col">Người phụ trách</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Ngày nộp</th>
                            <th scope="col" aria-label="Thao tác"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $application->application_code }}</td>
                                <td class="max-w-[180px]">
                                    <p class="truncate font-medium text-gray-950" title="{{ $application->citizen?->name }}">{{ $application->citizen?->name ?? 'Không còn dữ liệu' }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $application->citizen?->citizen_id }}">{{ $application->citizen?->citizen_id ?: 'Chưa có CCCD' }}</p>
                                    @if ($application->citizen?->trashed() || ($application->citizen && ! $application->citizen->is_active))
                                        <x-admin.badge variant="neutral">Không còn hoạt động</x-admin.badge>
                                    @endif
                                </td>
                                <td class="max-w-[200px]">
                                    <p class="truncate font-medium text-gray-950" title="{{ $application->serviceType?->name }}">{{ $application->serviceType?->name ?? 'Không còn dữ liệu' }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $application->serviceType?->code }}">{{ $application->serviceType?->code }}</p>
                                    @if ($application->serviceType?->trashed())
                                        <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                    @elseif ($application->serviceType && ! $application->serviceType->is_active)
                                        <x-admin.badge variant="neutral">Ngừng hoạt động</x-admin.badge>
                                    @endif
                                </td>
                                <td class="max-w-[160px]">
                                    <p class="truncate" title="{{ $application->serviceType?->responsibleDepartment?->name }}">{{ $application->serviceType?->responsibleDepartment?->name ?? 'Không còn dữ liệu' }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $application->serviceType?->responsibleDepartment?->code }}">{{ $application->serviceType?->responsibleDepartment?->code }}</p>
                                    @if ($application->serviceType?->responsibleDepartment?->trashed())
                                        <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                    @endif
                                </td>
                                <td class="max-w-[160px]">
                                    <p class="truncate" title="{{ $application->assignedStaff?->name }}">{{ $application->assignedStaff?->name ?: 'Chưa phân công' }}</p>
                                    @if ($application->assignedStaff?->trashed() || ($application->assignedStaff && ! $application->assignedStaff->is_active))
                                        <x-admin.badge variant="neutral">Không còn hoạt động</x-admin.badge>
                                    @endif
                                </td>
                                <td>
                                    <x-admin.badge :variant="$application->status->badgeVariant()">
                                        {{ $application->status->label() }}
                                    </x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap">
                                    <p>{{ $application->submitted_at?->format('d/m/Y H:i') ?: 'Chưa ghi nhận' }}</p>
                                    @if ($application->isOverdue())
                                        <x-admin.badge variant="danger">Quá hạn</x-admin.badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end">
                                        <x-admin.button variant="ghost" :href="route('admin.applications.show', $application)">Chi tiết</x-admin.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-4 sm:px-5">
                <p class="mb-3 text-sm text-gray-600">
                    Hiển thị {{ number_format($applications->firstItem()) }}–{{ number_format($applications->lastItem()) }} trong {{ number_format($applications->total()) }} kết quả
                </p>
                @if ($applications->hasPages())
                    {{ $applications->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </section>
@endsection
