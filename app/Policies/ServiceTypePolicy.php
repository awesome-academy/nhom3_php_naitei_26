<?php

namespace App\Policies;

use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessProtectedResources()
            && ($user->isSuperAdmin() || $user->isManager() || $user->isStaff());
    }

    public function view(User $user, ServiceType $serviceType): Response
    {
        return $this->canView($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->canAccessProtectedResources() && $user->isSuperAdmin();
    }

    public function update(User $user, ServiceType $serviceType): Response
    {
        return $this->authorizeSuperAdminMutation($user);
    }

    public function delete(User $user, ServiceType $serviceType): Response
    {
        return $this->authorizeSuperAdminMutation($user);
    }

    public function restore(User $user, ServiceType $serviceType): Response
    {
        return $this->authorizeSuperAdminMutation($user);
    }

    private function canView(User $user): bool
    {
        if (! $user->canAccessProtectedResources()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isManager() || $user->isStaff();
    }

    private function authorizeSuperAdminMutation(User $user): Response
    {
        if (! $user->canAccessProtectedResources()) {
            return Response::denyAsNotFound();
        }

        if (! $user->isSuperAdmin()) {
            return $this->canView($user)
                ? Response::deny('Chỉ Super Admin có thể thay đổi thông tin dịch vụ.')
                : Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
