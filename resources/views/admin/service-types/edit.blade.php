@extends('admin.layouts.app')

@section('title', 'Sửa dịch vụ')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-950">Chỉnh sửa dịch vụ</h1>
        <p class="mt-1 text-sm text-gray-600">Cập nhật thông tin dịch vụ {{ $serviceType->code }}</p>
    </div>

    <section class="admin-card">
        <div class="admin-card-header border-b border-border bg-gray-50/50 px-5 py-4">
            <h2 class="text-lg font-bold text-gray-950">Thông tin dịch vụ</h2>
        </div>
        
        <form action="{{ route('admin.service-types.update', $serviceType) }}" method="POST" class="admin-card-body">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Danh mục -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Danh mục <span class="text-danger">*</span></label>
                    <x-admin.searchable-select 
                        name="category_id" 
                        :value="$serviceType->category_id"
                        :options="$categories->map(fn($c) => ['value' => $c->id, 'label' => $c->name])->toArray()" 
                        placeholder="Chọn danh mục..."
                    />
                    @error('category_id') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <!-- Phòng ban -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Phòng ban phụ trách</label>
                    <x-admin.searchable-select 
                        name="responsible_department_id" 
                        :value="$serviceType->responsible_department_id"
                        :options="$departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name])->toArray()" 
                        placeholder="Chọn phòng ban (không bắt buộc)..."
                    />
                    @error('responsible_department_id') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <!-- Mã dịch vụ -->
                <div>
                    <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700">Mã dịch vụ <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code', $serviceType->code) }}" class="admin-input" required>
                    @error('code') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <!-- Tên dịch vụ -->
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Tên dịch vụ <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $serviceType->name) }}" class="admin-input" required>
                    @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <!-- Phí dịch vụ -->
                <div>
                    <label for="fee" class="mb-1.5 block text-sm font-medium text-gray-700">Phí dịch vụ (VNĐ) <span class="text-danger">*</span></label>
                    <input type="number" name="fee" id="fee" value="{{ old('fee', (int) $serviceType->fee) }}" class="admin-input" min="0" required>
                    @error('fee') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <!-- Thời gian xử lý -->
                <div>
                    <label for="processing_time_days" class="mb-1.5 block text-sm font-medium text-gray-700">Thời gian xử lý (Ngày) <span class="text-danger">*</span></label>
                    <input type="number" name="processing_time_days" id="processing_time_days" value="{{ old('processing_time_days', $serviceType->processing_time_days) }}" class="admin-input" min="1" required>
                    @error('processing_time_days') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Trạng thái -->
            <div class="mt-6 flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $serviceType->is_active)) class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                <label for="is_active" class="text-sm font-medium text-gray-700">Hoạt động (Cho phép người dân nộp hồ sơ)</label>
            </div>

            <!-- Mô tả -->
            <div class="mt-6">
                <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">Mô tả chi tiết</label>
                <textarea name="description" id="description" rows="3" class="admin-input !h-auto">{{ old('description', $serviceType->description) }}</textarea>
                @error('description') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <!-- Điều kiện & Yêu cầu -->
            <div class="mt-6">
                <label for="requirements" class="mb-1.5 block text-sm font-medium text-gray-700">Điều kiện thực hiện (Hiển thị cho người dân)</label>
                <textarea name="requirements" id="requirements" rows="3" class="admin-input !h-auto">{{ old('requirements', $serviceType->requirements) }}</textarea>
                @error('requirements') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <hr class="my-8 border-border">

            <!-- Yêu cầu Giấy tờ (Document Requirements) -->
            <div class="mt-6" x-data="{
                items: ({{ json_encode(old('document_requirements', $serviceType->document_requirements ?? [])) }}).map(r => ({
                    code: r.code ?? '',
                    name: r.name ?? r.label ?? '',
                    is_required: r.is_required ?? r.required ?? false,
                    type: r.type ?? 'mixed'
                })),
                add() { this.items.push({ code: '', name: '', is_required: true, type: 'mixed' }) },
                remove(index) { this.items.splice(index, 1) },
                slugify(value) {
                    return (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'giay-to'
                }
            }">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-950">Thành phần hồ sơ (Giấy tờ)</h3>
                        <p class="text-sm text-gray-600">Các loại giấy tờ người dân cần tải lên khi nộp hồ sơ.</p>
                    </div>
                    <x-admin.button type="button" variant="secondary" @click="add" class="!min-h-8 !px-3 !py-1 text-xs">
                        + Thêm giấy tờ
                    </x-admin.button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="flex-1">
                                <input type="hidden" :value="item.code" :name="`document_requirements[${index}][code]`">
                                <input type="text" x-model="item.name" :name="`document_requirements[${index}][name]`" class="admin-input w-full" placeholder="Tên giấy tờ (VD: CCCD, Sổ hộ khẩu...)" required>
                                <p class="mt-1 text-xs text-gray-500" x-text="item.code ? 'Mã: ' + item.code : 'Mã tự sinh: ' + slugify(item.name)"></p>
                            </div>
                            <div class="w-40">
                                <label class="mb-1 block text-xs font-medium text-gray-600">Loại file</label>
                                <select x-model="item.type" :name="`document_requirements[${index}][type]`" class="admin-select w-full">
                                    <option value="mixed">PDF hoặc Ảnh</option>
                                    <option value="pdf">Chỉ PDF</option>
                                    <option value="image">Chỉ Ảnh</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2 pt-2">
                                <input type="hidden" value="0" :name="`document_requirements[${index}][is_required]`">
                                <input type="checkbox" value="1" x-model="item.is_required" :name="`document_requirements[${index}][is_required]`" class="h-4 w-4 rounded border-gray-300 text-primary">
                                <label class="text-sm text-gray-700">Bắt buộc</label>
                            </div>
                            <button type="button" @click="remove(index)" class="pt-2 text-gray-400 hover:text-danger">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </template>
                    <div x-show="items.length === 0" class="rounded-lg border border-dashed border-gray-300 py-6 text-center text-gray-500 text-sm">
                        Chưa có yêu cầu giấy tờ nào.
                    </div>
                </div>
            </div>

            <hr class="my-8 border-border">

            <!-- Biểu mẫu điện tử (Form Schema) -->
            <div class="mt-6" x-data="{
                items: {{ json_encode(old('form_schema', $serviceType->form_schema ?? [])) }},
                add() { this.items.push({ name: '', type: 'text', is_required: true }) },
                remove(index) { this.items.splice(index, 1) }
            }">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-950">Biểu mẫu điện tử</h3>
                        <p class="text-sm text-gray-600">Các trường thông tin người dân cần điền thêm vào tờ khai trực tuyến.</p>
                    </div>
                    <x-admin.button type="button" variant="secondary" @click="add" class="!min-h-8 !px-3 !py-1 text-xs">
                        + Thêm trường thông tin
                    </x-admin.button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex flex-wrap items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:flex-nowrap">
                            <div class="w-full sm:w-1/2">
                                <input type="text" x-model="item.name" :name="`form_schema[${index}][name]`" class="admin-input w-full" placeholder="Tên trường (VD: Nơi sinh, Lý do...)" required>
                            </div>
                            <div class="w-full sm:w-1/4">
                                <select x-model="item.type" :name="`form_schema[${index}][type]`" class="admin-select w-full" required>
                                    <option value="text">Văn bản ngắn</option>
                                    <option value="number">Số</option>
                                    <option value="date">Ngày tháng</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2 pt-2 sm:w-auto">
                                <input type="hidden" value="0" :name="`form_schema[${index}][is_required]`">
                                <input type="checkbox" value="1" x-model="item.is_required" :name="`form_schema[${index}][is_required]`" class="h-4 w-4 rounded border-gray-300 text-primary">
                                <label class="text-sm text-gray-700 whitespace-nowrap">Bắt buộc</label>
                            </div>
                            <div class="ml-auto flex items-center pt-1.5 sm:pt-2">
                                <button type="button" @click="remove(index)" class="text-gray-400 hover:text-danger">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="items.length === 0" class="rounded-lg border border-dashed border-gray-300 py-6 text-center text-gray-500 text-sm">
                        Không có trường thông tin khai báo bổ sung.
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-8 flex justify-end gap-3 border-t border-border pt-5">
                <x-admin.button type="button" variant="ghost" :href="route('admin.service-types.index')">Hủy</x-admin.button>
                <x-admin.button type="submit">Lưu thay đổi</x-admin.button>
            </div>
        </form>
    </section>
@endsection
