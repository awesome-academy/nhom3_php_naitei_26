<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->canAccessProtectedResources() && $user->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'extensions:csv,txt', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Vui lòng chọn tệp CSV để nạp dữ liệu.',
            'csv_file.file' => 'Tệp tải lên không hợp lệ.',
            'csv_file.mimes' => 'Định dạng tệp phải là CSV (.csv).',
            'csv_file.max' => 'Dung lượng tệp CSV không được vượt quá 10MB.',
        ];
    }
}
