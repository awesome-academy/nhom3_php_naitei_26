<?php

namespace App\Http\Requests\Admin\Departments;

use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChangeDepartmentLeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');
        $actor = $this->user();

        if (! $department instanceof Department || ! $actor) {
            return false;
        }

        Gate::forUser($actor)->authorize('changeLeader', $department);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
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
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'leader_id.integer' => 'Lãnh đạo được chọn không hợp lệ.',
            'leader_id.exists' => 'Chỉ quản lý đang hoạt động và chưa được phân công vào phòng ban nào mới có thể làm lãnh đạo.',
            'version.required' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.integer' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
            'version.min' => 'Phiên bản dữ liệu không hợp lệ. Vui lòng tải lại trang.',
        ];
    }
}
