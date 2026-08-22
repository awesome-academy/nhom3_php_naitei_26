<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Actions\Department\TransferDepartmentMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\TransferDepartmentMemberRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TransferDepartmentMemberController extends Controller
{
    public function __invoke(
        TransferDepartmentMemberRequest $request,
        Department $department,
        User $member,
        TransferDepartmentMember $transferDepartmentMember,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $target = $transferDepartmentMember->handle(
            $department,
            $member,
            (int) $validated['target_department_id'],
            (int) $validated['source_version'],
            (int) $validated['target_version'],
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.show', $target)
            ->with('success', 'Đã điều chuyển nhân viên sang phòng ban đích. Tài khoản và lịch sử nghiệp vụ được giữ nguyên.');
    }
}
