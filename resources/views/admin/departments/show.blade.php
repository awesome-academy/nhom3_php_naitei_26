@extends('admin.layouts.app')

@section('title', $department->name)

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.departments.index') }}">
                &larr; Danh sách phòng ban
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-950">{{ $department->name }}</h1>
                <x-admin.badge :variant="$department->isArchived() ? 'neutral' : 'success'">
                    {{ $department->isArchived() ? 'Đã lưu trữ' : 'Hoạt động' }}
                </x-admin.badge>
            </div>
            <p class="mt-1 font-mono text-sm font-semibold text-primary">{{ $department->code }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('changeLeader', $department)
                <x-admin.button variant="secondary" data-dialog-open="change-department-leader">Đổi lãnh đạo</x-admin.button>
            @endcan
            @can('update', $department)
                <x-admin.button variant="secondary" :href="route('admin.departments.edit', $department)">Sửa</x-admin.button>
            @endcan
            @if ($department->isActive())
                @can('archive', $department)
                    <x-admin.button variant="secondary" class="!text-danger" data-dialog-open="archive-department">Lưu trữ</x-admin.button>
                @endcan
            @else
                @can('restore', $department)
                    <x-admin.button variant="secondary" class="!text-primary" data-dialog-open="restore-department">Hoàn tác</x-admin.button>
                @endcan
            @endif
        </div>
    </div>

    @if ($department->isArchived())
        <div class="mb-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700" role="status">
            Phòng ban này đã được lưu trữ và chỉ còn ở chế độ tra cứu. Thành viên, lãnh đạo, dịch vụ và lịch sử nghiệp vụ được giữ nguyên; mọi thay đổi cơ cấu đều bị vô hiệu hóa.
        </div>
    @endif

    @if ($department->leader_id && ! $department->hasEligibleLeader())
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            Lãnh đạo hiện tại không còn đủ điều kiện hoạt động. Tham chiếu vẫn được giữ để tra cứu và cần được quản trị viên cấp cao cập nhật.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="admin-card" aria-labelledby="department-information-title">
            <div class="admin-card-body">
                <h2 id="department-information-title" class="text-lg font-bold text-gray-950">Thông tin phòng ban</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Địa chỉ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $department->address ?: 'Chưa cập nhật' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lãnh đạo</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($department->leader)
                                <span class="font-semibold">{{ $department->leader->name }}</span>
                                <span class="block text-xs text-gray-500">{{ $department->leader->email }}</span>
                                <x-admin.badge class="mt-2" :variant="$department->hasEligibleLeader() ? 'success' : 'warning'">
                                    {{ $department->hasEligibleLeader() ? 'Quản lý đang hoạt động' : 'Không còn đủ điều kiện' }}
                                </x-admin.badge>
                            @else
                                <x-admin.badge variant="warning">Chưa có lãnh đạo</x-admin.badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ngày tạo</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $department->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phiên bản dữ liệu</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $department->lock_version }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-card" aria-labelledby="department-members-title">
            <div class="admin-card-body">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="department-members-title" class="text-lg font-bold text-gray-950">Thành viên</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $department->members->count() }} quan hệ hiện tại</p>
                    </div>
                    @can('addMember', $department)
                        <x-admin.button variant="secondary" data-dialog-open="add-department-member">Thêm</x-admin.button>
                    @endcan
                </div>

                @if ($department->members->isEmpty())
                    <p class="mt-4 rounded-xl bg-gray-50 px-3 py-4 text-sm text-gray-600">Chưa có thành viên.</p>
                @else
                    <div class="admin-table-wrap mt-4">
                        <table class="admin-table">
                            <caption class="sr-only">Danh sách thành viên phòng ban</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Thành viên</th>
                                    <th scope="col">Vai trò</th>
                                    <th scope="col"><span class="sr-only">Thao tác</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($department->members as $member)
                                    <tr>
                                        <td>
                                            <p class="whitespace-nowrap font-semibold text-gray-950">{{ $member->name }}</p>
                                            <p class="whitespace-nowrap text-xs text-gray-500">{{ $member->email }}</p>
                                            <x-admin.badge class="mt-1" :variant="$member->canAccessProtectedResources() ? 'success' : 'warning'">
                                                @if ($member->trashed())
                                                    Đã lưu trữ
                                                @elseif (! $member->is_active)
                                                    Không hoạt động
                                                @else
                                                    Đang hoạt động
                                                @endif
                                            </x-admin.badge>
                                        </td>
                                        <td>
                                            <x-admin.badge :variant="$member->isManager() ? 'manager' : 'staff'">
                                                {{ $member->isManager() ? 'Quản lý' : 'Nhân viên' }}
                                            </x-admin.badge>
                                        </td>
                                        <td class="text-right">
                                            @if ($department->leader_id === $member->id)
                                                <span class="whitespace-nowrap text-xs font-semibold text-gray-500">Lãnh đạo</span>
                                            @else
                                                @can('removeMember', $department)
                                                    @if (auth()->user()->isSuperAdmin() || $member->isStaff())
                                                        <div class="flex justify-end gap-3">
                                                            @if ($member->isStaff() && $member->canAccessProtectedResources())
                                                                <button type="button" class="text-xs font-semibold text-primary hover:underline" data-dialog-open="transfer-member-{{ $member->id }}">
                                                                    Điều chuyển
                                                                </button>
                                                            @endif
                                                            <button type="button" class="text-xs font-semibold text-danger hover:underline" data-dialog-open="remove-member-{{ $member->id }}">
                                                                Gỡ
                                                            </button>
                                                        </div>
                                                    @endif
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @can('changeLeader', $department)
        <x-admin.dialog
            id="change-department-leader"
            title="Thay đổi lãnh đạo"
            description="Chỉ quản lý đang hoạt động mới có thể được chọn. Lãnh đạo mới sẽ tự động trở thành thành viên."
            data-open-on-error="{{ $errors->has('leader_id') ? 'true' : 'false' }}"
        >
            <form
                method="POST"
                action="{{ route('admin.departments.leader.update', $department) }}"
                class="space-y-4"
                x-data="{ leaderId: @js((string) old('leader_id', '')) }"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="version" value="{{ $department->lock_version }}">
                <input type="hidden" name="leader_id" :value="leaderId">

                <div>
                    <label class="admin-label" for="department-leader-select">Quản lý phụ trách</label>
                    <select
                        id="department-leader-select"
                        class="admin-select"
                        x-model="leaderId"
                        @disabled($leaderCandidates->isEmpty())
                    >
                        <option value="">Chọn lãnh đạo phù hợp</option>
                        @foreach ($leaderCandidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->email }}</option>
                        @endforeach
                    </select>
                    @if ($leaderCandidates->isEmpty())
                        <p class="mt-1.5 text-sm text-gray-500">Đang chưa có lãnh đạo nào phù hợp.</p>
                    @else
                        <p class="mt-1.5 text-xs text-gray-500">Chỉ hiển thị quản lý đang hoạt động và chưa được phân công vào phòng ban nào.</p>
                    @endif
                    @error('leader_id')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-wrap justify-between gap-3 border-t border-border pt-4">
                    <button
                        type="submit"
                        class="text-sm font-semibold text-danger hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                        @click="leaderId = ''"
                        @disabled(! $department->leader_id)
                    >
                        Bỏ chỉ định lãnh đạo
                    </button>
                    <div class="flex gap-2">
                        <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                        <x-admin.button type="submit" x-bind:disabled="!leaderId">Lưu lãnh đạo</x-admin.button>
                    </div>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('archive', $department)
        <x-admin.dialog
            id="archive-department"
            title="Lưu trữ phòng ban"
            description="Phòng ban sẽ bị loại khỏi danh sách hoạt động và các lựa chọn quan hệ mới. Thành viên, lãnh đạo, dịch vụ và lịch sử nghiệp vụ vẫn được giữ nguyên."
            data-open-on-error="{{ $errors->hasAny(['confirmation', 'version']) ? 'true' : 'false' }}"
        >
            <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" class="space-y-4">
                @csrf
                @method('DELETE')
                <input type="hidden" name="archive_department_id" value="{{ $department->id }}">
                <input type="hidden" name="confirmation" value="archive">
                <input type="hidden" name="version" value="{{ $department->lock_version }}">
                <p class="text-sm text-gray-700">
                    Bạn có chắc muốn lưu trữ <strong>{{ $department->name }}</strong>? Đây không phải thao tác xóa vĩnh viễn và không làm mất dữ liệu liên quan.
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

    @can('restore', $department)
        <x-admin.dialog
            id="restore-department"
            title="Khôi phục phòng ban"
            description="Phòng ban sẽ được chuyển về trạng thái đang hoạt động và xuất hiện lại trong các danh sách chọn."
        >
            <form method="POST" action="{{ route('admin.departments.restore', $department) }}" class="space-y-4">
                @csrf
                <p class="text-sm text-gray-700">
                    Bạn có chắc chắn muốn khôi phục hoạt động cho phòng ban <strong>{{ $department->name }}</strong>?
                </p>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Xác nhận hoàn tác</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('addMember', $department)
        <x-admin.dialog
            id="add-department-member"
            title="Thêm thành viên"
            description="Chọn nhân viên đang hoạt động và chưa được phân công vào phòng ban nào."
            data-open-on-error="{{ $errors->has('user_id') ? 'true' : 'false' }}"
        >
            <form
                method="POST"
                action="{{ route('admin.departments.members.store', $department) }}"
                class="space-y-4"
                x-data="{ staffId: @js((string) old('user_id', '')) }"
            >
                @csrf
                <input type="hidden" name="version" value="{{ $department->lock_version }}">
                <input type="hidden" name="user_id" :value="staffId">
                <div>
                    <label class="admin-label" for="department-staff-select">Nhân viên</label>
                    <select
                        id="department-staff-select"
                        class="admin-select"
                        x-model="staffId"
                        @disabled($staffCandidates->isEmpty())
                    >
                        <option value="">Chọn nhân viên phù hợp</option>
                        @foreach ($staffCandidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->email }}</option>
                        @endforeach
                    </select>
                    @if ($staffCandidates->isEmpty())
                        <p class="mt-1.5 text-sm text-gray-500">Đang chưa có staff nào phù hợp.</p>
                    @else
                        <p class="mt-1.5 text-xs text-gray-500">Chỉ hiển thị Staff đang hoạt động và chưa được phân công vào phòng ban nào.</p>
                    @endif
                    @error('user_id')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit" x-bind:disabled="!staffId">Thêm thành viên</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @foreach ($department->members as $member)
        @if ($department->leader_id !== $member->id && (auth()->user()->isSuperAdmin() || $member->isStaff()))
            @can('removeMember', $department)
                @if ($member->isStaff() && $member->canAccessProtectedResources())
                    <x-admin.dialog
                        id="transfer-member-{{ $member->id }}"
                        title="Điều chuyển nhân viên"
                        description="Nhân viên sẽ được thêm vào phòng ban đích và gỡ khỏi phòng ban nguồn trong cùng một thao tác nguyên tử. Tài khoản và lịch sử nghiệp vụ không bị thay đổi."
                        data-open-on-error="{{ $errors->hasAny(['target_department_id', 'source_version', 'target_version', 'member']) && (string) old('transfer_member_id') === (string) $member->id ? 'true' : 'false' }}"
                    >
                        <form
                            method="POST"
                            action="{{ route('admin.departments.members.transfer', [$department, $member]) }}"
                            class="space-y-4"
                            x-data="candidateCombobox({ url: @js(route('admin.departments.members.transfer-targets', [$department, $member])) })"
                            @click.outside="close()"
                            @submit="beginSubmit()"
                        >
                            @csrf
                            <input type="hidden" name="transfer_member_id" value="{{ $member->id }}">
                            <input type="hidden" name="source_version" value="{{ $department->lock_version }}">
                            <input type="hidden" name="target_department_id" :value="selected?.id ?? ''">
                            <input type="hidden" name="target_version" :value="selected?.version ?? ''">

                            <dl class="grid gap-3 rounded-xl bg-gray-50 p-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nhân viên</dt>
                                    <dd class="mt-1 font-semibold text-gray-950">{{ $member->name }}</dd>
                                    <dd class="text-xs text-gray-500">{{ $member->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phòng ban nguồn</dt>
                                    <dd class="mt-1 font-semibold text-gray-950">{{ $department->name }}</dd>
                                    <dd class="font-mono text-xs text-gray-500">{{ $department->code }}</dd>
                                </div>
                            </dl>

                            <div>
                                <label class="admin-label" for="transfer-target-search-{{ $member->id }}">Phòng ban đích</label>
                                <input
                                    id="transfer-target-search-{{ $member->id }}"
                                    class="admin-input"
                                    type="search"
                                    x-model="query"
                                    @input.debounce.300ms="handleInput()"
                                    @keydown.arrow-down.prevent="move(1)"
                                    @keydown.arrow-up.prevent="move(-1)"
                                    @keydown.enter.prevent="chooseActive()"
                                    @keydown.escape="close()"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-controls="transfer-target-options-{{ $member->id }}"
                                    :aria-expanded="open.toString()"
                                    autocomplete="off"
                                    placeholder="Nhập tên hoặc mã phòng ban"
                                >
                                <div id="transfer-target-options-{{ $member->id }}" x-show="open" x-cloak class="mt-1 max-h-56 overflow-y-auto rounded-xl border border-border p-1" role="listbox">
                                    <p x-show="loading" class="px-3 py-2 text-sm text-gray-500">Đang tìm kiếm...</p>
                                    <p x-show="error" x-text="error" class="px-3 py-2 text-sm text-danger"></p>
                                    <p x-show="!loading && !error && query.trim().length >= 2 && items.length === 0" class="px-3 py-2 text-sm text-gray-500">Không có phòng ban đích phù hợp.</p>
                                    <template x-for="(item, index) in items" :key="item.id">
                                        <button type="button" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-blue-50" :class="activeIndex === index ? 'bg-blue-50' : ''" role="option" @mousedown.prevent="select(item)">
                                            <span class="block text-sm font-semibold" x-text="item.name"></span>
                                            <span class="block font-mono text-xs text-gray-500" x-text="item.code"></span>
                                        </button>
                                    </template>
                                    <p x-show="hasMore" class="px-3 py-2 text-xs text-gray-500">Còn kết quả khác. Hãy nhập từ khóa cụ thể hơn.</p>
                                </div>
                                @error('target_department_id')<p class="admin-field-error">{{ $message }}</p>@enderror
                                @error('target_version')<p class="admin-field-error">{{ $message }}</p>@enderror
                                @error('source_version')<p class="admin-field-error">{{ $message }}</p>@enderror
                                @error('member')<p class="admin-field-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex justify-end gap-2 border-t border-border pt-4">
                                <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                                <x-admin.button type="submit" x-bind:disabled="!selected || loading || submitting">
                                    <span x-show="!submitting">Xác nhận điều chuyển</span>
                                    <span x-show="submitting" x-cloak>Đang điều chuyển...</span>
                                </x-admin.button>
                            </div>
                        </form>
                    </x-admin.dialog>
                @endif

                <x-admin.dialog
                    id="remove-member-{{ $member->id }}"
                    title="Gỡ thành viên"
                    description="Thao tác này chỉ gỡ quan hệ với phòng ban; tài khoản và lịch sử nghiệp vụ vẫn được giữ nguyên."
                    data-open-on-error="{{ $errors->has('member') ? 'true' : 'false' }}"
                >
                    <form method="POST" action="{{ route('admin.departments.members.destroy', [$department, $member]) }}" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="version" value="{{ $department->lock_version }}">
                        <p class="text-sm text-gray-700">Bạn có chắc muốn gỡ <strong>{{ $member->name }}</strong> khỏi {{ $department->name }}?</p>
                        @error('member')<p class="admin-field-error">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 border-t border-border pt-4">
                            <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                            <x-admin.button type="submit" variant="danger">Gỡ thành viên</x-admin.button>
                        </div>
                    </form>
                </x-admin.dialog>
            @endcan
        @endif
    @endforeach

    <section class="admin-card mt-5" aria-labelledby="department-services-title">
        <div class="admin-card-body">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="department-services-title" class="text-lg font-bold text-gray-950">Dịch vụ liên kết</h2>
                    <x-admin.badge variant="info">Chỉ đọc</x-admin.badge>
                </div>
                <p class="mt-1 text-sm text-gray-500">Thông tin phục vụ tra cứu cơ cấu; không có thao tác thay đổi dịch vụ trong tính năng này.</p>
            </div>

            @if ($department->serviceTypes->isEmpty())
                <p class="mt-4 rounded-xl bg-gray-50 px-3 py-4 text-sm text-gray-600">Chưa có dịch vụ liên kết.</p>
            @else
                <div class="admin-table-wrap mt-4">
                    <table class="admin-table">
                        <caption class="sr-only">Dịch vụ do phòng ban phụ trách</caption>
                        <thead>
                            <tr>
                                <th scope="col">Mã</th>
                                <th scope="col">Tên dịch vụ</th>
                                <th scope="col">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($department->serviceTypes as $serviceType)
                                <tr>
                                    <td class="font-mono font-semibold">{{ $serviceType->code }}</td>
                                    <td>{{ $serviceType->name }}</td>
                                    <td>
                                        <x-admin.badge :variant="$serviceType->trashed() ? 'neutral' : ($serviceType->is_active ? 'success' : 'warning')">
                                            @if ($serviceType->trashed())
                                                Đã lưu trữ
                                            @elseif ($serviceType->is_active)
                                                Hoạt động
                                            @else
                                                Không hoạt động
                                            @endif
                                        </x-admin.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
