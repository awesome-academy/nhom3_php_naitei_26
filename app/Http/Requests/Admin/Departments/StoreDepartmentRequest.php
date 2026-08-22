<?php

namespace App\Http\Requests\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
                Rule::unique('departments', 'code'),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'leader_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('role', UserRole::Manager->value)
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên phòng ban.',
            'name.max' => 'Tên phòng ban không được vượt quá 255 ký tự.',
            'code.required' => 'Vui lòng nhập mã phòng ban.',
            'code.min' => 'Mã phòng ban phải có ít nhất 2 ký tự.',
            'code.max' => 'Mã phòng ban không được vượt quá 50 ký tự.',
            'code.regex' => 'Mã chỉ được gồm chữ cái, số và dấu gạch nối hoặc gạch dưới.',
            'code.unique' => 'Mã phòng ban đã tồn tại, kể cả trong dữ liệu đã lưu trữ.',
            'address.max' => 'Địa chỉ không được vượt quá 1.000 ký tự.',
            'leader_id.integer' => 'Lãnh đạo được chọn không hợp lệ.',
            'leader_id.exists' => 'Chỉ quản lý đang hoạt động mới có thể làm lãnh đạo phòng ban.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeName($this->input('name')),
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'address' => $this->normalizeAddress($this->input('address')),
        ]);
    }

    private function normalizeName(mixed $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
    }

    private function normalizeAddress(mixed $value): ?string
    {
        $address = trim((string) $value);

        return $address === '' ? null : $address;
    }
}
