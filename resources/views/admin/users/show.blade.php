@extends('admin.layouts.app')

@section('title', $user->name)

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.users.index') }}">&larr; Danh sách người dùng</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-950">{{ $user->name }}</h1>
                <x-admin.badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Đang hoạt động' : 'Đã vô hiệu hóa' }}</x-admin.badge>
                <x-admin.badge :variant="$user->role->badgeVariant()">{{ $user->role->label() }}</x-admin.badge>
            </div>
            <p class="mt-1 text-sm text-gray-600">{{ $user->email }}</p>
        </div>

        @can('changeStatus', $user)
            @if ($user->is_active && $deactivationBlockReason === null)
                <x-admin.button variant="danger" data-dialog-open="change-user-status">Vô hiệu hóa tài khoản</x-admin.button>
            @elseif (! $user->is_active)
                <x-admin.button data-dialog-open="change-user-status">Kích hoạt tài khoản</x-admin.button>
            @endif
        @endcan
    </div>

    @if ($deactivationBlockReason)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            Không thể vô hiệu hóa tài khoản: {{ $deactivationBlockReason }}
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="admin-card lg:col-span-2" aria-labelledby="user-profile-title">
            <div class="admin-card-body">
                <h2 id="user-profile-title" class="text-lg font-bold text-gray-950">Thông tin tài khoản</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['Mã định danh công dân', $user->citizen_id ?: 'Không có'],
                        ['Số điện thoại', $user->phone ?: 'Chưa cập nhật'],
                        ['Ngày sinh', $user->date_of_birth?->format('d/m/Y') ?: 'Chưa cập nhật'],
                        ['Giới tính', $user->gender ?: 'Chưa cập nhật'],
                        ['Ngày tạo', $user->created_at?->format('d/m/Y H:i')],
                        ['Cập nhật gần nhất', $user->updated_at?->format('d/m/Y H:i')],
                    ] as [$label, $value])
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Địa chỉ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->address ?: 'Chưa cập nhật' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-card" aria-labelledby="user-application-title">
            <div class="admin-card-body">
                <h2 id="user-application-title" class="text-lg font-bold text-gray-950">Tóm tắt hồ sơ</h2>
                <dl class="mt-4 space-y-3">
                    <div class="flex items-center justify-between gap-3"><dt class="text-sm text-gray-600">Hồ sơ đã nộp</dt><dd class="font-bold text-gray-950">{{ $user->submitted_applications_count }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-sm text-gray-600">Hồ sơ được phân công</dt><dd class="font-bold text-gray-950">{{ $user->assigned_applications_count }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-sm text-gray-600">Phân công chưa hoàn thành</dt><dd class="font-bold text-gray-950">{{ $user->unfinished_assigned_applications_count }}</dd></div>
                </dl>
            </div>
        </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        @foreach ([
            ['title' => 'Phòng ban thành viên', 'items' => $user->departments],
            ['title' => 'Phòng ban lãnh đạo', 'items' => $user->ledDepartments],
        ] as $section)
            <section class="admin-card">
                <div class="admin-card-body">
                    <h2 class="text-lg font-bold text-gray-950">{{ $section['title'] }}</h2>
                    @if ($section['items']->isEmpty())
                        <p class="mt-4 rounded-xl bg-gray-50 px-3 py-4 text-sm text-gray-600">Không có quan hệ phòng ban.</p>
                    @else
                        <ul class="mt-4 divide-y divide-border">
                            @foreach ($section['items'] as $department)
                                <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                    <div><p class="font-semibold text-gray-950">{{ $department->name }}</p><p class="font-mono text-xs text-gray-500">{{ $department->code }}</p></div>
                                    <x-admin.badge :variant="$department->trashed() ? 'neutral' : 'success'">{{ $department->trashed() ? 'Đã lưu trữ' : 'Hoạt động' }}</x-admin.badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    @can('changeStatus', $user)
        @if ((! $user->is_active) || $deactivationBlockReason === null)
            <x-admin.dialog
                id="change-user-status"
                :title="$user->is_active ? 'Vô hiệu hóa tài khoản' : 'Kích hoạt tài khoản'"
                :description="$user->is_active ? 'Người dùng sẽ mất quyền truy cập vào tài nguyên được bảo vệ từ yêu cầu tiếp theo. Dữ liệu và lịch sử vẫn được giữ nguyên.' : 'Người dùng sẽ có thể truy cập lại theo quyền hiện có của tài khoản.'"
                data-open-on-error="{{ $errors->has('is_active') ? 'true' : 'false' }}"
            >
                <form method="POST" action="{{ route('admin.users.status.update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                    <p class="text-sm text-gray-700">Xác nhận {{ $user->is_active ? 'vô hiệu hóa' : 'kích hoạt' }} tài khoản <strong>{{ $user->name }}</strong>.</p>
                    @error('is_active')<p class="admin-field-error">{{ $message }}</p>@enderror
                    <div class="flex justify-end gap-2 border-t border-border pt-4">
                        <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                        <x-admin.button type="submit" :variant="$user->is_active ? 'danger' : 'primary'">Xác nhận</x-admin.button>
                    </div>
                </form>
            </x-admin.dialog>
        @endif
    @endcan
@endsection
