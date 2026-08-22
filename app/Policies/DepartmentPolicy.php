<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessProtectedResources()
            && ($user->isSuperAdmin() || $user->isManager());
    }

    public function view(User $user, Department $department): Response
    {
        return $this->canView($user, $department)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->canAccessProtectedResources() && $user->isSuperAdmin();
    }

    public function update(User $user, Department $department): Response
    {
        return $this->authorizeSuperAdminMutation($user, $department);
    }

    public function archive(User $user, Department $department): Response
    {
        return $this->authorizeSuperAdminMutation($user, $department);
    }

    public function delete(User $user, Department $department): Response
    {
        return $this->archive($user, $department);
    }

    public function changeLeader(User $user, Department $department): Response
    {
        return $this->authorizeSuperAdminMutation($user, $department);
    }

    public function addMember(User $user, Department $department): Response
    {
        return $this->authorizeMembershipMutation($user, $department);
    }

    public function removeMember(User $user, Department $department): Response
    {
        return $this->authorizeMembershipMutation($user, $department);
    }

    public function transferMember(
        User $user,
        Department $source,
        Department $target,
    ): Response {
        if (! $user->canAccessProtectedResources()) {
            return Response::denyAsNotFound();
        }

        if ($source->isArchived() || $target->isArchived()) {
            return Response::deny('Phòng ban đã lưu trữ chỉ có thể được tra cứu.');
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if ($user->isManager()
            && $source->leader_id === $user->getKey()
            && $target->leader_id === $user->getKey()) {
            return Response::allow();
        }

        return $this->canView($user, $source)
            ? Response::deny('Bạn không có quyền điều chuyển nhân sự tới phòng ban đích.')
            : Response::denyAsNotFound();
    }

    public function restore(User $user, Department $department): Response
    {
        if (! $user->canAccessProtectedResources()) {
            return Response::denyAsNotFound();
        }

        if (! $user->isSuperAdmin()) {
            return $this->canView($user, $department)
                ? Response::deny('Chỉ Super Admin có thể khôi phục phòng ban.')
                : Response::denyAsNotFound();
        }

        return $department->isArchived()
            ? Response::allow()
            : Response::deny('Phòng ban đang hoạt động không cần khôi phục.');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }

    private function canView(User $user, Department $department): bool
    {
        if (! $user->canAccessProtectedResources()) {
            return false;
        }

        return $user->isSuperAdmin()
            || ($user->isManager() && $department->leader_id === $user->getKey());
    }

    private function authorizeSuperAdminMutation(User $user, Department $department): Response
    {
        if (! $user->canAccessProtectedResources()) {
            return Response::denyAsNotFound();
        }

        if (! $user->isSuperAdmin()) {
            return $this->canView($user, $department)
                ? Response::deny('Chỉ Super Admin có thể thay đổi thông tin phòng ban.')
                : Response::denyAsNotFound();
        }

        return $department->isActive()
            ? Response::allow()
            : Response::deny('Phòng ban đã lưu trữ chỉ có thể được tra cứu.');
    }

    private function authorizeMembershipMutation(User $user, Department $department): Response
    {
        if (! $this->canView($user, $department)) {
            return Response::denyAsNotFound();
        }

        if ($department->isArchived()) {
            return Response::deny('Phòng ban đã lưu trữ chỉ có thể được tra cứu.');
        }

        if ($user->isSuperAdmin()
            || ($user->isManager() && $department->leader_id === $user->getKey())) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
