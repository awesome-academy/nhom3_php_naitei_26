@extends('admin.layouts.app')

@section('title', 'Danh sách dịch vụ công')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Dịch vụ công</h1>
            <p class="mt-1 text-sm text-gray-600">Quản lý các dịch vụ công được cung cấp trên hệ thống.</p>
        </div>

        <div class="flex items-center gap-3">
            @if (auth()->user()?->isSuperAdmin())
                <x-admin.button variant="secondary" :href="route('admin.export', array_merge(['resource' => 'services'], request()->query()))">
                    Xuất CSV
                </x-admin.button>
            @endif
            @can('create', \App\Models\ServiceType::class)
                <x-admin.button :href="route('admin.service-types.create')">Tạo dịch vụ</x-admin.button>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <section class="admin-card mb-5" aria-labelledby="service-filter-title">
        <h2 id="service-filter-title" class="sr-only">Bộ lọc dịch vụ công</h2>
        <form action="{{ route('admin.service-types.index') }}" method="GET" class="p-4 sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_220px_180px_auto] xl:items-end">
                <div>
                    <label for="service-search" class="admin-label">Tìm kiếm</label>
                    <input type="search" name="search" id="service-search" value="{{ request('search') }}" class="admin-input" placeholder="Mã hoặc tên dịch vụ...">
                </div>
                
                <div>
                    <label for="service-category" class="admin-label">Danh mục</label>
                    <select name="category" id="service-category" class="admin-select">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) request('category') === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="service-status" class="admin-label">Trạng thái</label>
                    <select name="status" id="service-status" class="admin-select">
                        <option value="active" @selected(($status ?? 'active') === 'active')>Đang hoạt động</option>
                        <option value="archived" @selected(($status ?? '') === 'archived')>Đã lưu trữ</option>
                        <option value="all" @selected(($status ?? '') === 'all')>Tất cả</option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if($hasFilters ?? false)
                        <x-admin.button variant="secondary" :href="route('admin.service-types.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>
    </section>

    <section class="admin-card" aria-labelledby="service-results-title">
        <h2 id="service-results-title" class="sr-only">Kết quả tra cứu dịch vụ</h2>

        @if ($serviceTypes->isEmpty())
            <div class="px-5 py-12 text-center">
                @if ($hasFilters ?? false)
                    <h3 class="text-lg font-bold text-gray-950">Không có kết quả phù hợp</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">Thử thay đổi từ khóa, danh mục hoặc trạng thái đang lọc.</p>
                    <x-admin.button class="mt-5" variant="secondary" :href="route('admin.service-types.index')">Xóa bộ lọc</x-admin.button>
                @else
                    <h3 class="text-lg font-bold text-gray-950">Chưa có dịch vụ nào</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">
                        Dịch vụ được tạo sẽ hiển thị tại đây.
                    </p>
                    @can('create', \App\Models\ServiceType::class)
                        <x-admin.button class="mt-5" :href="route('admin.service-types.create')">Tạo dịch vụ đầu tiên</x-admin.button>
                    @endcan
                @endif
            </div>
        @else
            <div class="admin-table-wrap rounded-none border-x-0 border-t-0" tabindex="0" aria-label="Bảng dịch vụ công có thể cuộn ngang">
                <table class="admin-table w-full min-w-[760px]">
                    <caption class="sr-only">Danh sách dịch vụ</caption>
                    <thead>
                        <tr>
                            <th scope="col">Dịch vụ công</th>
                            <th scope="col">Danh mục</th>
                            <th scope="col">Phòng ban phụ trách</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col" aria-label="Thao tác"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($serviceTypes as $service)
                            <tr>
                                <td>
                                    <p class="font-semibold text-gray-950">{{ $service->name }}</p>
                                    <p class="mt-0.5 font-mono text-xs font-semibold text-primary">{{ $service->code }}</p>
                                </td>
                                <td>{{ $service->category?->name ?? 'Không xác định' }}</td>
                                <td>{{ $service->responsibleDepartment?->name ?: 'Chưa phân công' }}</td>
                                <td>
                                    @if($service->isArchived())
                                        <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                                    @elseif($service->is_active)
                                        <x-admin.badge variant="success">Hoạt động</x-admin.badge>
                                    @else
                                        <x-admin.badge variant="warning">Tạm ngưng</x-admin.badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    <div class="flex justify-end gap-2">
                                        <x-admin.button variant="ghost" :href="route('admin.service-types.show', $service)">Chi tiết</x-admin.button>
                                        @if (!$service->isArchived())
                                            @can('update', $service)
                                                <x-admin.button variant="secondary" :href="route('admin.service-types.edit', $service)">Sửa</x-admin.button>
                                            @endcan
                                            @can('delete', $service)
                                                <x-admin.button variant="secondary" class="!text-danger" data-dialog-open="archive-service-{{ $service->id }}">Lưu trữ</x-admin.button>
                                            @endcan
                                        @else
                                            @can('restore', $service)
                                                <x-admin.button variant="secondary" class="!text-primary" data-dialog-open="restore-service-{{ $service->id }}">Hoàn tác</x-admin.button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border px-4 py-4 sm:px-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-sm text-gray-600">
                    Hiển thị {{ number_format($serviceTypes->firstItem() ?? 0) }}–{{ number_format($serviceTypes->lastItem() ?? 0) }} trong {{ number_format($serviceTypes->total()) }} kết quả
                </p>
                @if ($serviceTypes->hasPages())
                    {{ $serviceTypes->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </section>

    @foreach ($serviceTypes as $service)
        @if (!$service->isArchived())
            @can('delete', $service)
                <x-admin.dialog
                    id="archive-service-{{ $service->id }}"
                    title="Lưu trữ dịch vụ công"
                    description="Dịch vụ sẽ bị ẩn khỏi danh sách cho công dân nộp hồ sơ mới. Toàn bộ hồ sơ cũ liên quan vẫn được bảo toàn."
                >
                    <form method="POST" action="{{ route('admin.service-types.destroy', $service) }}" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm text-gray-700">
                            Bạn có chắc chắn muốn lưu trữ dịch vụ <strong>{{ $service->name }}</strong>? Đây không phải thao tác xóa vĩnh viễn.
                        </p>
                        <div class="flex justify-end gap-2 border-t border-border pt-4">
                            <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                            <x-admin.button type="submit" variant="danger">Xác nhận lưu trữ</x-admin.button>
                        </div>
                    </form>
                </x-admin.dialog>
            @endcan
        @else
            @can('restore', $service)
                <x-admin.dialog
                    id="restore-service-{{ $service->id }}"
                    title="Khôi phục dịch vụ công"
                    description="Dịch vụ sẽ được khôi phục về trạng thái hoạt động."
                >
                    <form method="POST" action="{{ route('admin.service-types.restore', $service) }}" class="space-y-4">
                        @csrf
                        <p class="text-sm text-gray-700">
                            Bạn có chắc chắn muốn khôi phục dịch vụ <strong>{{ $service->name }}</strong>?
                        </p>
                        <div class="flex justify-end gap-2 border-t border-border pt-4">
                            <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                            <x-admin.button type="submit">Xác nhận hoàn tác</x-admin.button>
                        </div>
                    </form>
                </x-admin.dialog>
            @endcan
        @endif
    @endforeach
@endsection
