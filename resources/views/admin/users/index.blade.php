@extends('admin.layouts.app')

@section('title', 'Quản lý người dùng')

@section('content')
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Người dùng</h1>
            <p class="mt-1 text-sm text-gray-600">Tra cứu tài khoản và trạng thái truy cập. Vai trò, hồ sơ định danh và cơ cấu tổ chức được quản lý bởi feature tương ứng.</p>
        </div>
        @if (auth()->user()?->isSuperAdmin())
            <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                <x-admin.button variant="secondary" :href="route('admin.export', array_merge(['resource' => request('role') === 'staff' || request('role') === 'manager' ? 'staff' : 'citizens'], request()->query()))">
                    Xuất CSV
                </x-admin.button>
                <x-admin.button :href="route('admin.users.import')">
                    Nhập dữ liệu CSV
                </x-admin.button>
            </div>
        @endif
    </div>

    <section class="admin-card" aria-labelledby="user-results-title">
        <h2 id="user-results-title" class="sr-only">Kết quả tra cứu người dùng</h2>
        <form method="GET" action="{{ route('admin.users.index') }}" class="border-b border-border p-4 sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(260px,1fr)_190px_190px_auto] xl:items-end">
                <div>
                    <label class="admin-label" for="user-search">Tìm kiếm</label>
                    <input id="user-search" class="admin-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Tên, email hoặc mã định danh công dân" aria-invalid="{{ $errors->has('search') ? 'true' : 'false' }}">
                    @error('search')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="user-role">Vai trò</label>
                    <select id="user-role" class="admin-select" name="role">
                        <option value="">Tất cả vai trò</option>
                        @foreach ($roleOptions as $role)
                            <option value="{{ $role->value }}" @selected(($filters['role'] ?? null) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="user-status">Trạng thái</label>
                    <select id="user-status" class="admin-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(($filters['status'] ?? null) === 'active')>Đang hoạt động</option>
                        <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Đã vô hiệu hóa</option>
                    </select>
                    @error('status')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if ($hasFilters)
                        <x-admin.button variant="secondary" :href="route('admin.users.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>

        @if ($users->isEmpty())
            <div class="px-5 py-14 text-center">
                <h3 class="text-base font-semibold text-gray-950">Không tìm thấy người dùng phù hợp</h3>
                <p class="mt-1 text-sm text-gray-600">Hãy kiểm tra từ khóa hoặc thay đổi bộ lọc.</p>
                @if ($hasFilters)
                    <x-admin.button class="mt-4" variant="secondary" :href="route('admin.users.index')">Xóa bộ lọc</x-admin.button>
                @endif
            </div>
        @else
            <div class="admin-table-wrap hidden md:block">
                <table class="admin-table">
                    <caption class="sr-only">Danh sách tài khoản người dùng</caption>
                    <thead>
                        <tr>
                            <th scope="col">Người dùng</th>
                            <th scope="col">Vai trò</th>
                            <th scope="col">Tổ chức</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Ngày tạo</th>
                            <th scope="col"><span class="sr-only">Thao tác</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <p class="font-semibold text-gray-950">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    @if ($user->citizen_id)<p class="mt-1 font-mono text-xs text-gray-500">{{ $user->citizen_id }}</p>@endif
                                </td>
                                <td><x-admin.badge :variant="$user->role->badgeVariant()">{{ $user->role->label() }}</x-admin.badge></td>
                                <td class="text-sm text-gray-700">
                                    <span class="block">{{ $user->departments_count }} phòng ban thành viên</span>
                                    <span class="block text-xs text-gray-500">{{ $user->led_departments_count }} phòng ban lãnh đạo</span>
                                </td>
                                <td><x-admin.badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Đang hoạt động' : 'Đã vô hiệu hóa' }}</x-admin.badge></td>
                                <td class="whitespace-nowrap">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-right"><x-admin.button variant="ghost" :href="route('admin.users.show', $user)">Chi tiết</x-admin.button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-border md:hidden">
                @foreach ($users as $user)
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0"><h3 class="truncate font-semibold text-gray-950">{{ $user->name }}</h3><p class="truncate text-sm text-gray-600">{{ $user->email }}</p></div>
                            <x-admin.badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Hoạt động' : 'Vô hiệu hóa' }}</x-admin.badge>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                            <x-admin.badge :variant="$user->role->badgeVariant()">{{ $user->role->label() }}</x-admin.badge>
                            <span>{{ $user->departments_count }} phòng ban</span>
                        </div>
                        <x-admin.button class="mt-4 w-full" variant="secondary" :href="route('admin.users.show', $user)">Xem chi tiết</x-admin.button>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-border px-4 py-4 sm:px-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-sm text-gray-600">Hiển thị {{ number_format($users->firstItem() ?? 0) }}–{{ number_format($users->lastItem() ?? 0) }} trong {{ number_format($users->total()) }} kết quả</p>
                @if ($users->hasPages()){{ $users->onEachSide(1)->links() }}@endif
            </div>
        @endif
    </section>
@endsection
