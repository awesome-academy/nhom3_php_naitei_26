@extends('admin.layouts.app')

@section('title', 'Danh sách danh mục dịch vụ')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Danh mục dịch vụ</h1>
            <p class="mt-1 text-sm text-gray-600">Quản lý các danh mục phân loại dịch vụ công trên hệ thống.</p>
        </div>

        @can('create', \App\Models\ServiceCategory::class)
            <x-admin.button :href="route('admin.service-categories.create')">Tạo danh mục</x-admin.button>
        @endcan
    </div>

    <!-- Filters -->
    <section class="admin-card mb-5" aria-labelledby="category-filter-title">
        <h2 id="category-filter-title" class="sr-only">Bộ lọc danh mục</h2>
        <form method="GET" action="{{ route('admin.service-categories.index') }}" class="p-4 sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(260px,1fr)_200px_auto] xl:items-end">
                <div>
                    <label class="admin-label" for="category-search">Tìm kiếm</label>
                    <input id="category-search" class="admin-input" type="search" name="search" value="{{ $search ?? '' }}" maxlength="100" placeholder="Tên, mã hoặc mô tả danh mục">
                </div>
                <div>
                    <label class="admin-label" for="category-status">Trạng thái</label>
                    <select id="category-status" class="admin-select" name="status">
                        <option value="active" @selected(($status ?? 'active') === 'active')>Đang hoạt động</option>
                        <option value="archived" @selected(($status ?? '') === 'archived')>Đã lưu trữ</option>
                        <option value="all" @selected(($status ?? '') === 'all')>Tất cả</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if ($hasFilters ?? false)
                        <x-admin.button variant="secondary" :href="route('admin.service-categories.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>
    </section>

    <section class="admin-card" aria-labelledby="category-results-title">
        <h2 id="category-results-title" class="sr-only">Kết quả tra cứu danh mục</h2>

        @if ($categories->isEmpty())
            <div class="px-5 py-12 text-center">
                @if ($hasFilters ?? false)
                    <h3 class="text-lg font-bold text-gray-950">Không có kết quả phù hợp</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">Thử thay đổi từ khóa hoặc trạng thái đang lọc.</p>
                    <x-admin.button class="mt-5" variant="secondary" :href="route('admin.service-categories.index')">Xóa bộ lọc</x-admin.button>
                @else
                    <h3 class="text-lg font-bold text-gray-950">Chưa có danh mục nào</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">
                        Danh mục được tạo sẽ xuất hiện tại đây để sử dụng khi phân loại dịch vụ công.
                    </p>
                    @can('create', \App\Models\ServiceCategory::class)
                        <x-admin.button class="mt-5" :href="route('admin.service-categories.create')">Tạo danh mục đầu tiên</x-admin.button>
                    @endcan
                @endif
            </div>
        @else
            <div class="admin-table-wrap rounded-none border-x-0 border-t-0" tabindex="0" aria-label="Bảng danh mục có thể cuộn ngang">
                <table class="admin-table w-full min-w-[760px]">
                    <caption class="sr-only">Danh sách danh mục dịch vụ</caption>
                    <thead>
                        <tr>
                            <th scope="col">Mã</th>
                            <th scope="col">Tên danh mục</th>
                            <th scope="col">Số dịch vụ</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col" aria-label="Thao tác"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ strtoupper($category->code) }}</td>
                                <td>
                                    <p class="font-semibold text-gray-950">{{ $category->name }}</p>
                                    @if($category->description)
                                        <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $category->description }}</p>
                                    @endif
                                </td>
                                <td>{{ number_format($category->service_types_count ?? 0) }}</td>
                                <td>
                                    <x-admin.badge :variant="$category->isArchived() ? 'neutral' : 'success'">
                                        {{ $category->isArchived() ? 'Đã lưu trữ' : 'Hoạt động' }}
                                    </x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap">
                                    <div class="flex justify-end gap-2">
                                        <x-admin.button variant="ghost" :href="route('admin.service-categories.show', $category)">Chi tiết</x-admin.button>
                                        @if ($category->isActive())
                                            @can('update', $category)
                                                <x-admin.button variant="secondary" :href="route('admin.service-categories.edit', $category)">Sửa</x-admin.button>
                                            @endcan
                                            @can('delete', $category)
                                                <x-admin.button variant="secondary" class="!text-danger" data-dialog-open="archive-category-{{ $category->id }}">Lưu trữ</x-admin.button>
                                            @endcan
                                        @else
                                            @can('restore', $category)
                                                <x-admin.button variant="secondary" class="!text-primary" data-dialog-open="restore-category-{{ $category->id }}">Hoàn tác</x-admin.button>
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
                    Hiển thị {{ number_format($categories->firstItem() ?? 0) }}–{{ number_format($categories->lastItem() ?? 0) }} trong {{ number_format($categories->total()) }} kết quả
                </p>
                @if ($categories->hasPages())
                    {{ $categories->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </section>

    @foreach ($categories as $category)
        @if ($category->isActive())
            @can('delete', $category)
                <x-admin.dialog
                    id="archive-category-{{ $category->id }}"
                    title="Lưu trữ danh mục dịch vụ"
                    description="Danh mục sẽ bị ẩn khỏi các danh sách chọn tạo mới dịch vụ. Dịch vụ và hồ sơ cũ thuộc danh mục này vẫn được bảo toàn."
                >
                    <form method="POST" action="{{ route('admin.service-categories.destroy', $category) }}" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm text-gray-700">
                            Bạn có chắc chắn muốn lưu trữ danh mục <strong>{{ $category->name }}</strong>? Đây không phải thao tác xóa vĩnh viễn.
                        </p>
                        <div class="flex justify-end gap-2 border-t border-border pt-4">
                            <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                            <x-admin.button type="submit" variant="danger">Xác nhận lưu trữ</x-admin.button>
                        </div>
                    </form>
                </x-admin.dialog>
            @endcan
        @else
            @can('restore', $category)
                <x-admin.dialog
                    id="restore-category-{{ $category->id }}"
                    title="Khôi phục danh mục dịch vụ"
                    description="Danh mục sẽ được kích hoạt lại và xuất hiện trong các danh sách chọn."
                >
                    <form method="POST" action="{{ route('admin.service-categories.restore', $category) }}" class="space-y-4">
                        @csrf
                        <p class="text-sm text-gray-700">
                            Bạn có chắc chắn muốn khôi phục danh mục <strong>{{ $category->name }}</strong> về trạng thái hoạt động?
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
