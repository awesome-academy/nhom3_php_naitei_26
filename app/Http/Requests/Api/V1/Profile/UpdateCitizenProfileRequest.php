<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCitizenProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'required', 'date'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'address' => ['sometimes', 'required', 'string', 'max:1000'],
            'email_notifications_enabled' => ['sometimes', 'required', 'boolean'],
            'email' => ['prohibited'],
            'citizen_id' => ['prohibited'],
            'role' => ['prohibited'],
            'password' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.string' => 'Họ và tên không hợp lệ.',
            'name.max' => 'Họ và tên không được vượt quá :max ký tự.',
            'date_of_birth.required' => 'Vui lòng nhập ngày sinh.',
            'date_of_birth.date' => 'Ngày sinh không hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max' => 'Địa chỉ không được vượt quá :max ký tự.',
            'email_notifications_enabled.required' => 'Vui lòng chọn tùy chọn nhận thông báo qua email.',
            'email_notifications_enabled.boolean' => 'Tùy chọn nhận thông báo qua email không hợp lệ.',
            'email.prohibited' => 'Không thể thay đổi email trong hồ sơ.',
            'citizen_id.prohibited' => 'Không thể thay đổi số CCCD.',
            'role.prohibited' => 'Không thể thay đổi vai trò.',
            'password.prohibited' => 'Không thể thay đổi mật khẩu tại đây.',
            'is_active.prohibited' => 'Không thể thay đổi trạng thái tài khoản.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['name', 'phone', 'address'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = trim((string) $this->input($field));
            }
        }

        $this->merge($normalized);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Dữ liệu không hợp lệ.',
                errors: $validator->errors()->toArray(),
                status: 422,
            ),
        );
    }
}
