@php
    $editing = isset($department);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" novalidate>
    @csrf
    @if ($editing)
        @method('PATCH')
        <input type="hidden" name="version" value="{{ old('version', $department->lock_version) }}">
    @endif

    <div>
        <label class="admin-label" for="department-name">Tên phòng ban <span class="text-danger">*</span></label>
        <input
            id="department-name"
            class="admin-input"
            type="text"
            name="name"
            value="{{ old('name', $department->name ?? '') }}"
            maxlength="255"
            required
            autocomplete="organization"
            aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
            @if ($errors->has('name')) aria-describedby="department-name-error" @endif
        >
        @error('name')
            <p id="department-name-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="admin-label" for="department-code">Mã phòng ban <span class="text-danger">*</span></label>
        <input
            id="department-code"
            class="admin-input uppercase"
            type="text"
            name="code"
            value="{{ old('code', $department->code ?? '') }}"
            minlength="2"
            maxlength="50"
            required
            spellcheck="false"
            aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
            @if ($errors->has('code')) aria-describedby="department-code-help department-code-error" @else aria-describedby="department-code-help" @endif
        >
        <p id="department-code-help" class="mt-1.5 text-xs text-gray-500">
            Dùng chữ cái, số, dấu gạch nối hoặc gạch dưới. Mã sẽ được lưu bằng chữ hoa.
        </p>
        @error('code')
            <p id="department-code-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="admin-label" for="department-address">Địa chỉ</label>
        <textarea
            id="department-address"
            class="admin-input min-h-28 resize-y"
            name="address"
            maxlength="1000"
            rows="4"
            aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}"
            @if ($errors->has('address')) aria-describedby="department-address-error" @endif
        >{{ old('address', $department->address ?? '') }}</textarea>
        @error('address')
            <p id="department-address-error" class="admin-field-error">{{ $message }}</p>
        @enderror
    </div>

    @unless ($editing)
        <div
            x-data="candidateCombobox({
                url: @js(route('admin.departments.manager-candidates')),
                initial: @js($selectedLeader ? [
                    'id' => $selectedLeader->id,
                    'name' => $selectedLeader->name,
                    'email' => $selectedLeader->email,
                    'role' => $selectedLeader->role->value,
                    'role_label' => $selectedLeader->role->label(),
                ] : null),
            })"
            @click.outside="close()"
        >
            <label class="admin-label" for="department-leader-search">Lãnh đạo</label>
            <input type="hidden" name="leader_id" :value="selected?.id ?? ''">
            <div class="relative">
                <input
                    id="department-leader-search"
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
                    aria-controls="department-leader-options"
                    :aria-expanded="open.toString()"
                    autocomplete="off"
                    placeholder="Nhập ít nhất 2 ký tự để tìm quản lý"
                >
                <div
                    id="department-leader-options"
                    x-show="open"
                    x-cloak
                    class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-border bg-white p-1 shadow-lg"
                    role="listbox"
                >
                    <p x-show="loading" class="px-3 py-2 text-sm text-gray-500">Đang tìm kiếm...</p>
                    <p x-show="error" x-text="error" class="px-3 py-2 text-sm text-danger"></p>
                    <p x-show="!loading && !error && items.length === 0" class="px-3 py-2 text-sm text-gray-500">Không có quản lý phù hợp.</p>
                    <template x-for="(item, index) in items" :key="item.id">
                        <button
                            type="button"
                            class="block w-full rounded-lg px-3 py-2 text-left hover:bg-blue-50"
                            :class="activeIndex === index ? 'bg-blue-50' : ''"
                            role="option"
                            :aria-selected="selected?.id === item.id"
                            @mousedown.prevent="select(item)"
                        >
                            <span class="block text-sm font-semibold text-gray-950" x-text="item.name"></span>
                            <span class="block text-xs text-gray-500" x-text="item.email"></span>
                        </button>
                    </template>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">Có thể để trống và thiết lập lãnh đạo sau.</p>
            @error('leader_id')
                <p class="admin-field-error">{{ $message }}</p>
            @enderror
        </div>
    @endunless

    <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-5">
        <x-admin.button variant="secondary" :href="$cancelUrl">Hủy</x-admin.button>
        <x-admin.button type="submit">{{ $submitLabel }}</x-admin.button>
    </div>
</form>
