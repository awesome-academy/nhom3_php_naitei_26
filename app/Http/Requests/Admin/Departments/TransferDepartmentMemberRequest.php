<?php

namespace App\Http\Requests\Admin\Departments;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class TransferDepartmentMemberRequest extends FormRequest
{
    private const UNAVAILABLE_TARGET = 'Phòng ban đích không khả dụng. Vui lòng chọn một phòng ban đang hoạt động trong phạm vi của bạn.';

    public function authorize(): bool
    {
        $source = $this->route('department');
        $member = $this->route('member');
        $actor = $this->user();

        if (! $source instanceof Department || ! $member instanceof User || ! $actor) {
            return false;
        }

        Gate::forUser($actor)->authorize('removeMember', $source);

        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'target_department_id' => ['required', 'integer'],
            'source_version' => ['required', 'integer', 'min:0'],
            'target_version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $source = $this->route('department');
            $member = $this->route('member');
            $actor = $this->user();

            if (! $source instanceof Department || ! $member instanceof User || ! $actor) {
                return;
            }

            if (! $member->isStaff() || ! $member->canAccessProtectedResources()) {
                $validator->errors()->add('member', 'Chỉ nhân viên đang hoạt động mới có thể được điều chuyển.');
            }

            if ($validator->errors()->has('target_department_id')) {
                return;
            }

            $targetId = (int) $this->input('target_department_id');
            $target = Department::query()
                ->visibleTo($actor)
                ->whereKey($targetId)
                ->first();

            $isUnavailable = ! $target
                || $target->is($source)
                || Gate::forUser($actor)->inspect('transferMember', [$source, $target])->denied()
                || DB::table('department_user')
                    ->where('department_id', $targetId)
                    ->where('user_id', $member->getKey())
                    ->exists();

            if ($isUnavailable) {
                $validator->errors()->add('target_department_id', self::UNAVAILABLE_TARGET);
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'target_department_id.required' => self::UNAVAILABLE_TARGET,
            'target_department_id.integer' => self::UNAVAILABLE_TARGET,
            'source_version.required' => 'Phiên bản phòng ban nguồn không hợp lệ. Vui lòng tải lại trang.',
            'source_version.integer' => 'Phiên bản phòng ban nguồn không hợp lệ. Vui lòng tải lại trang.',
            'source_version.min' => 'Phiên bản phòng ban nguồn không hợp lệ. Vui lòng tải lại trang.',
            'target_version.required' => 'Phiên bản phòng ban đích không hợp lệ. Vui lòng chọn lại phòng ban đích.',
            'target_version.integer' => 'Phiên bản phòng ban đích không hợp lệ. Vui lòng chọn lại phòng ban đích.',
            'target_version.min' => 'Phiên bản phòng ban đích không hợp lệ. Vui lòng chọn lại phòng ban đích.',
        ];
    }
}
