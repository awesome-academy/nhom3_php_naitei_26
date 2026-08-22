<?php

namespace App\Http\Requests\Admin\ActivityLogs;

use Illuminate\Foundation\Http\FormRequest;

class IndexActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ($user->isStaff() || $user->isManager() || $user->isSuperAdmin())
            && $user->canAccessProtectedResources();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->normalizedString('q'),
            'actor_id' => $this->normalizedNullableValue('actor_id'),
            'action' => $this->normalizedString('action'),
            'subject_type' => $this->normalizedString('subject_type'),
            'date_from' => $this->normalizedString('date_from'),
            'date_to' => $this->normalizedString('date_to'),
            'page' => $this->query('page', 1),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.string' => 'Từ khóa tìm kiếm không hợp lệ.',
            'q.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
            'actor_id.integer' => 'Người thực hiện không hợp lệ.',
            'actor_id.min' => 'Người thực hiện không hợp lệ.',
            'action.string' => 'Hành động không hợp lệ.',
            'action.max' => 'Hành động không hợp lệ.',
            'subject_type.string' => 'Loại đối tượng không hợp lệ.',
            'subject_type.max' => 'Loại đối tượng không hợp lệ.',
            'date_from.date_format' => 'Ngày bắt đầu không hợp lệ.',
            'date_to.date_format' => 'Ngày kết thúc không hợp lệ.',
            'date_to.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
            'page.required' => 'Trang danh sách không hợp lệ.',
            'page.integer' => 'Trang danh sách không hợp lệ.',
            'page.min' => 'Trang danh sách không hợp lệ.',
        ];
    }

    private function normalizedString(string $key): mixed
    {
        $value = $this->query($key);

        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizedNullableValue(string $key): mixed
    {
        $value = $this->query($key);

        return is_string($value) && trim($value) === '' ? null : $value;
    }
}
