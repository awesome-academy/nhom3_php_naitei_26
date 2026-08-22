<?php

namespace App\Http\Requests\Admin\Applications;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Application $application */
        $application = $this->route('application');

        $departmentId = $application->serviceType?->responsible_department_id;

        return [
            'staff_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id'),
                Rule::exists('department_user', 'user_id')->where('department_id', $departmentId),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_id.required' => 'Vui lòng chọn staff để phân công.',
            'staff_id.exists' => 'Nhân viên không tồn tại hoặc không thuộc phòng ban phụ trách dịch vụ của hồ sơ.',
        ];
    }
}
