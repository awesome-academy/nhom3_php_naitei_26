@extends('admin.layouts.app')

@section('title', 'Danh sách phòng ban')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Phòng ban</h1>
            <p class="mt-1 text-sm text-gray-600">Tra cứu cơ cấu phòng ban, lãnh đạo, thành viên và dịch vụ trong phạm vi được phép.</p>
        </div>
        <div class="flex items-center gap-3">
            <x-admin.button variant="secondary" :href="route('admin.export', array_merge(['resource' => 'departments'], request()->query()))">
                Xuất CSV
            </x-admin.button>
            @can('create', \App\Models\Department::class)
                <x-admin.button :href="route('admin.departments.create')">Tạo phòng ban</x-admin.button>
            @endcan
        </div>
    </div>

    <section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Thống kê phòng ban">
        @foreach ([
            ['label' => 'Tổng phòng ban', 'value' => $stats['total'], 'class' => 'text-gray-950'],
            ['label' => 'Đang hoạt động', 'value' => $stats['active'], 'class' => 'text-emerald-700'],
            ['label' => 'Thiếu lãnh đạo hợp lệ', 'value' => $stats['missing_leader'], 'class' => 'text-amber-700'],
            ['label' => 'Nhân viên đang thuộc phòng ban', 'value' => $stats['staff_memberships'], 'class' => 'text-primary'],
        ] as $stat)
            <article class="admin-card">
                <div class="admin-card-body">
                    <p class="text-[13px] font-medium text-gray-500">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $stat['class'] }}">{{ number_format($stat['value']) }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="admin-card" aria-labelledby="department-results-title" x-data="departmentFilters()">
        <h2 id="department-results-title" class="sr-only">Kết quả tra cứu phòng ban</h2>
        <form method="GET" action="{{ route('admin.departments.index') }}" class="border-b border-border p-4 sm:p-5" @submit="beginLoading()">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_220px_180px_auto] xl:items-end">
                <div>
                    <label class="admin-label" for="department-search">Tìm kiếm</label>
                    <input id="department-search" class="admin-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Tên, mã hoặc địa chỉ" aria-invalid="{{ $errors->has('search') ? 'true' : 'false' }}">
                    @error('search')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="department-manager">Lãnh đạo</label>
                    <select id="department-manager" class="admin-select" name="manager_id">
                        <option value="">Tất cả lãnh đạo</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}" @selected((string) ($filters['manager_id'] ?? '') === (string) $manager->id)>
                                {{ $manager->name }}{{ $manager->canAccessProtectedResources() ? '' : ' — không hoạt động' }}
                            </option>
                        @endforeach
                    </select>
                    @error('manager_id')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="department-status">Trạng thái</label>
                    <select id="department-status" class="admin-select" name="status">
                        <option value="active" @selected($filters['status'] === 'active')>Đang hoạt động</option>
                        <option value="archived" @selected($filters['status'] === 'archived')>Đã lưu trữ</option>
                        <option value="all" @selected($filters['status'] === 'all')>Tất cả</option>
                    </select>
                    @error('status')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit" x-bind:disabled="loading">
                        <span x-show="!loading">Áp dụng</span>
                        <span x-show="loading" x-cloak>Đang tải...</span>
                    </x-admin.button>
                    @if ($hasFilters)
                        <x-admin.button variant="secondary" :href="route('admin.departments.index')" @click="beginLoading()">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>

        <div x-show="loading" x-cloak class="border-b border-blue-100 bg-blue-50 px-5 py-3 text-sm font-medium text-blue-800" role="status" aria-live="polite">
            Đang cập nhật danh sách phòng ban…
        </div>
        @if ($errors->any())
            <div class="m-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:m-5" role="alert">
                Không thể áp dụng bộ lọc. Vui lòng kiểm tra lại dữ liệu tìm kiếm.
            </div>
        @endif

        @if ($departments->isEmpty())
            <div class="px-5 py-12 text-center">
                @if ($stats['total'] === 0)
                    <h3 class="text-lg font-bold text-gray-950">Chưa có phòng ban</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">Dữ liệu phòng ban được tạo sẽ xuất hiện tại đây.</p>
                    @can('create', \App\Models\Department::class)
                        <x-admin.button class="mt-5" :href="route('admin.departments.create')">Tạo phòng ban đầu tiên</x-admin.button>
                    @endcan
                @else
                    <h3 class="text-lg font-bold text-gray-950">Không có kết quả phù hợp</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">Thử thay đổi từ khóa, lãnh đạo hoặc trạng thái đang chọn.</p>
                    <x-admin.button class="mt-5" variant="secondary" :href="route('admin.departments.index')">Xóa bộ lọc</x-admin.button>
                @endif
            </div>
        @else
            <div class="admin-table-wrap rounded-none border-x-0 border-t-0" tabindex="0" aria-label="Bảng phòng ban có thể cuộn ngang">
                <table class="admin-table min-w-[920px]">
                    <caption class="sr-only">Danh sách phòng ban trong phạm vi được phép</caption>
                    <thead>
                        <tr>
                            <th scope="col">Mã</th>
                            <th scope="col">Phòng ban</th>
                            <th scope="col">Lãnh đạo</th>
                            <th scope="col">Thành viên</th>
                            <th scope="col">Dịch vụ</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col" aria-label="Thao tác"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $department->code }}</td>
                                <td>
                                    <p class="font-semibold text-gray-950">{{ $department->name }}</p>
                                    <p class="mt-1 max-w-xs truncate text-xs text-gray-500">{{ $department->address ?: 'Chưa có địa chỉ' }}</p>
                                </td>
                                <td>
                                    @if ($department->leader)
                                        <p class="font-medium">{{ $department->leader->name }}</p>
                                        @if ($department->hasEligibleLeader())
                                            <span class="mt-1 block text-xs text-gray-500">Đang hoạt động</span>
                                        @else
                                            <x-admin.badge class="mt-1" variant="warning">Cần cập nhật</x-admin.badge>
                                        @endif
                                    @else
                                        <x-admin.badge variant="warning">Chưa có lãnh đạo</x-admin.badge>
                                    @endif
                                </td>
                                <td>{{ number_format($department->members_count) }}</td>
                                <td>{{ number_format($department->service_types_count) }}</td>
                                <td>
                                    <x-admin.badge :variant="$department->isArchived() ? 'neutral' : 'success'">
                                        {{ $department->isArchived() ? 'Đã lưu trữ' : 'Hoạt động' }}
                                    </x-admin.badge>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <x-admin.button variant="ghost" :href="route('admin.departments.show', $department)">Chi tiết</x-admin.button>
                                        @can('update', $department)
                                            <x-admin.button variant="secondary" :href="route('admin.departments.edit', $department)">Chỉnh sửa</x-admin.button>
                                        @endcan
                                        @can('archive', $department)
                                            <x-admin.button variant="danger" data-dialog-open="archive-department-{{ $department->id }}">Lưu trữ</x-admin.button>
                                        @endcan
                                    </div>
                                    @can('archive', $department)
                                        <x-admin.dialog
                                            id="archive-department-{{ $department->id }}"
                                            title="Lưu trữ phòng ban"
                                            description="Phòng ban sẽ bị loại khỏi danh sách hoạt động và các lựa chọn quan hệ mới. Thành viên, lãnh đạo, dịch vụ và lịch sử nghiệp vụ vẫn được giữ nguyên."
                                            data-open-on-error="{{ $errors->hasAny(['confirmation', 'version']) && (string) old('archive_department_id') === (string) $department->id ? 'true' : 'false' }}"
                                        >
                                            <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" class="space-y-4">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="archive_department_id" value="{{ $department->id }}">
                                                <input type="hidden" name="confirmation" value="archive">
                                                <input type="hidden" name="version" value="{{ $department->lock_version }}">
                                                <p class="text-sm text-gray-700">
                                                    Bạn có chắc muốn lưu trữ <strong>{{ $department->name }}</strong>? Đây không phải thao tác xóa vĩnh viễn.
                                                </p>
                                                @error('confirmation')<p class="admin-field-error">{{ $message }}</p>@enderror
                                                @error('version')<p class="admin-field-error">{{ $message }}</p>@enderror
                                                <div class="flex justify-end gap-2 border-t border-border pt-4">
                                                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                                                    <x-admin.button type="submit" variant="danger">Xác nhận lưu trữ</x-admin.button>
                                                </div>
                                            </form>
                                        </x-admin.dialog>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-4 sm:px-5">
                <p class="mb-3 text-sm text-gray-600">
                    Hiển thị {{ number_format($departments->firstItem()) }}–{{ number_format($departments->lastItem()) }} trong {{ number_format($departments->total()) }} kết quả
                </p>
                @if ($departments->hasPages())
                    {{ $departments->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </section>
@endsection
