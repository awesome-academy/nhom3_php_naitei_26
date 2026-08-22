<?php

namespace App\Http\Requests\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDepartmentMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');
        $actor = $this->user();

        if (! $department instanceof Department || ! $actor) {
            return false;
        }

        Gate::forUser($actor)->authorize('addMember', $department);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $roles = $this->user()?->isSuperAdmin()
            ? [UserRole::Staff->value, UserRole::Manager->value]
            : [UserRole::Staff->value];

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->whereIn('role', $roles)
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Vui lòng chọn thành viên.',
            'user_id.integer' => 'Thành viên được chọn không hợp lệ.',
            'user_id.exists' => 'Chỉ nhân viên hoặc quản lý đang hoạt động và phù hợp phạm vi mới có thể được thêm.',
            'version.required' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.integer' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.min' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
        ];
    }
}
