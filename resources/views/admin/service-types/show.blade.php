@extends('admin.layouts.app')

@section('title', $serviceType->name)

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.service-types.index') }}">
                &larr; Danh sách dịch vụ
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-950">{{ $serviceType->name }}</h1>
                @if($serviceType->isArchived())
                    <x-admin.badge variant="neutral">Đã lưu trữ</x-admin.badge>
                @elseif($serviceType->is_active)
                    <x-admin.badge variant="success">Hoạt động</x-admin.badge>
                @else
                    <x-admin.badge variant="warning">Tạm ngưng</x-admin.badge>
                @endif
            </div>
            <p class="mt-1 font-mono text-sm font-semibold text-primary">{{ $serviceType->code }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (!$serviceType->isArchived())
                @can('update', $serviceType)
                    <x-admin.button variant="secondary" :href="route('admin.service-types.edit', $serviceType)">Chỉnh sửa</x-admin.button>
                @endcan
                @can('delete', $serviceType)
                    <x-admin.button variant="secondary" class="!text-danger" data-dialog-open="archive-service">Lưu trữ</x-admin.button>
                @endcan
            @else
                @can('restore', $serviceType)
                    <x-admin.button variant="secondary" class="!text-primary" data-dialog-open="restore-service">Hoàn tác</x-admin.button>
                @endcan
            @endif
        </div>
    </div>

    @if ($serviceType->isArchived())
        <div class="mb-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700" role="status">
            Dịch vụ này đã được lưu trữ và ẩn khỏi danh sách nộp hồ sơ công dân. Toàn bộ hồ sơ cũ liên quan vẫn được lưu giữ nguyên vẹn.
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-5">
            <section class="admin-card" aria-labelledby="service-information-title">
                <div class="admin-card-body">
                    <h2 id="service-information-title" class="text-lg font-bold text-gray-950">Thông tin chung</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mã dịch vụ</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $serviceType->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Danh mục</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($serviceType->category)
                                    <a href="{{ route('admin.service-categories.show', $serviceType->category_id) }}" class="text-primary hover:underline">
                                        {{ $serviceType->category->name }}
                                    </a>
                                @else
                                    <span class="text-gray-500">Chưa gán danh mục</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phòng ban phụ trách</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($serviceType->responsibleDepartment)
                                    <a href="{{ route('admin.departments.show', $serviceType->responsibleDepartment->id) }}" class="text-primary hover:underline">
                                        {{ $serviceType->responsibleDepartment->name }}
                                    </a>
                                @else
                                    <span class="text-gray-500">Chưa phân công</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phí dịch vụ</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ number_format($serviceType->fee) }} đ</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Thời gian xử lý</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $serviceType->processing_time_days }} ngày làm việc</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="admin-card" aria-labelledby="service-details-title">
                <div class="admin-card-body space-y-6">
                    <h2 id="service-details-title" class="text-lg font-bold text-gray-950">Mô tả & Điều kiện</h2>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Mô tả chi tiết</h3>
                        <div class="prose prose-sm text-gray-700">
                            {!! nl2br(e($serviceType->description ?: 'Chưa có thông tin mô tả.')) !!}
                        </div>
                    </div>

                    <hr class="border-border">

                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Điều kiện thực hiện</h3>
                        <div class="prose prose-sm text-gray-700">
                            {!! nl2br(e($serviceType->requirements ?: 'Không có điều kiện đặc biệt.')) !!}
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-5">
            <section class="admin-card" aria-labelledby="service-documents-title">
                <div class="admin-card-body">
                    <h2 id="service-documents-title" class="text-lg font-bold text-gray-950">Giấy tờ cần nộp</h2>
                    <p class="mt-1 text-sm text-gray-500">Danh sách tài liệu người dân phải tải lên.</p>
                    
                    <ul class="mt-4 divide-y divide-gray-100">
                        @forelse($serviceType->document_requirements ?? [] as $doc)
                            <li class="py-3 flex items-start justify-between gap-3">
                                <span class="text-sm text-gray-900 font-medium">{{ $doc['name'] ?? 'Không tên' }}</span>
                                @if($doc['is_required'] ?? false)
                                    <x-admin.badge variant="warning" class="shrink-0 text-xs py-0">Bắt buộc</x-admin.badge>
                                @endif
                            </li>
                        @empty
                            <li class="py-4 text-center text-sm text-gray-500 rounded-lg border border-dashed border-gray-300">Không yêu cầu nộp giấy tờ.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <section class="admin-card" aria-labelledby="service-schema-title">
                <div class="admin-card-body">
                    <h2 id="service-schema-title" class="text-lg font-bold text-gray-950">Biểu mẫu điện tử</h2>
                    <p class="mt-1 text-sm text-gray-500">Các trường thông tin phải điền thêm.</p>
                    
                    <ul class="mt-4 divide-y divide-gray-100">
                        @forelse($serviceType->form_schema ?? [] as $field)
                            <li class="py-3 flex items-start justify-between gap-3">
                                <div>
                                    <span class="block text-sm text-gray-900 font-medium">{{ $field['name'] ?? 'Không tên' }}</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">Kiểu: {{ $field['type'] ?? 'text' }}</span>
                                </div>
                                @if($field['is_required'] ?? false)
                                    <x-admin.badge variant="warning" class="shrink-0 text-xs py-0">Bắt buộc</x-admin.badge>
                                @endif
                            </li>
                        @empty
                            <li class="py-4 text-center text-sm text-gray-500 rounded-lg border border-dashed border-gray-300">Không có trường nhập liệu bổ sung.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>
    </div>

    @can('delete', $serviceType)
        <x-admin.dialog
            id="archive-service"
            title="Lưu trữ dịch vụ công"
            description="Dịch vụ sẽ bị ẩn khỏi danh sách cho công dân nộp hồ sơ mới. Toàn bộ hồ sơ cũ liên quan vẫn được bảo toàn."
        >
            <form method="POST" action="{{ route('admin.service-types.destroy', $serviceType) }}" class="space-y-4">
                @csrf
                @method('DELETE')
                <p class="text-sm text-gray-700">
                    Bạn có chắc chắn muốn lưu trữ dịch vụ <strong>{{ $serviceType->name }}</strong>? Đây không phải thao tác xóa vĩnh viễn.
                </p>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit" variant="danger">Xác nhận lưu trữ</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('restore', $serviceType)
        <x-admin.dialog
            id="restore-service"
            title="Khôi phục dịch vụ công"
            description="Dịch vụ sẽ được khôi phục về trạng thái hoạt động."
        >
            <form method="POST" action="{{ route('admin.service-types.restore', $serviceType) }}" class="space-y-4">
                @csrf
                <p class="text-sm text-gray-700">
                    Bạn có chắc chắn muốn khôi phục dịch vụ <strong>{{ $serviceType->name }}</strong>?
                </p>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Xác nhận hoàn tác</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan
@endsection
