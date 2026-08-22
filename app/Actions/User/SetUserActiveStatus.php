<?php

namespace App\Actions\User;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class SetUserActiveStatus
{
    public function handle(
        User $target,
        bool $desiredActive,
        User $actor,
        ?Request $request = null,
    ): User {
        return DB::transaction(function () use ($target, $desiredActive, $actor, $request): User {
            [$freshActor, $freshTarget, $activeSuperAdmins] = $this->lockFreshUsers(
                $actor,
                $target,
            );

            Gate::forUser($freshActor)->authorize('changeStatus', $freshTarget);

            if ($freshTarget->is_active === $desiredActive) {
                return $freshTarget;
            }

            if (! $desiredActive) {
                $this->guardDeactivation($freshActor, $freshTarget, $activeSuperAdmins);
            }

            $before = $this->snapshot($freshTarget);
            $freshTarget->is_active = $desiredActive;
            $freshTarget->save();
            $after = $this->snapshot($freshTarget);

            ActivityLog::query()->create([
                'actor_id' => $freshActor->getKey(),
                'action' => $desiredActive ? 'user.activated' : 'user.deactivated',
                'subject_type' => $freshTarget->getMorphClass(),
                'subject_id' => $freshTarget->getKey(),
                'description' => $desiredActive ? 'Đã kích hoạt tài khoản người dùng.' : 'Đã vô hiệu hóa tài khoản người dùng.',
                'metadata' => [
                    'actor' => $this->snapshot($freshActor),
                    'subject' => $after,
                    'before' => $before,
                    'after' => $after,
                ],
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);

            return $freshTarget->refresh();
        });
    }

    /**
     * @return array{User, User, Collection<int, User>}
     */
    private function lockFreshUsers(User $actor, User $target): array
    {
        $activeSuperAdmins = collect();

        if ($target->role === UserRole::SuperAdmin) {
            $lockedSuperAdmins = User::query()
                ->where('role', UserRole::SuperAdmin->value)
                ->where(function ($query) use ($actor, $target): void {
                    $query
                        ->where('is_active', true)
                        ->orWhereIn('id', [$actor->getKey(), $target->getKey()]);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $activeSuperAdmins = $lockedSuperAdmins
                ->filter(fn (User $user): bool => $user->is_active)
                ->values();
        } else {
            User::withTrashed()
                ->whereIn('id', collect([$actor->getKey(), $target->getKey()])->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }

        $freshActor = User::withTrashed()->findOrFail($actor->getKey());
        $freshTarget = User::withTrashed()->findOrFail($target->getKey());

        return [$freshActor, $freshTarget, $activeSuperAdmins];
    }

    /** @param Collection<int, User> $activeSuperAdmins */
    private function guardDeactivation(User $actor, User $target, Collection $activeSuperAdmins): void
    {
        if ($target->isSuperAdmin() && $activeSuperAdmins->count() <= 1) {
            $this->fail('Không thể vô hiệu hóa quản trị viên cấp cao đang hoạt động cuối cùng.');
        }

        if ($actor->is($target)) {
            $this->fail('Bạn không thể vô hiệu hóa chính tài khoản đang đăng nhập.');
        }

        if ($target->isManager() && Department::query()->where('leader_id', $target->getKey())->exists()) {
            $this->fail('Không thể vô hiệu hóa quản lý đang lãnh đạo một phòng ban hoạt động.');
        }

        if (($target->isStaff() || $target->isManager()) && Application::query()
            ->where('assigned_staff_id', $target->getKey())
            ->whereNotIn('status', ApplicationStatus::completedValues())
            ->exists()) {
            $this->fail('Không thể vô hiệu hóa cán bộ đang phụ trách hồ sơ chưa hoàn thành.');
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => (bool) $user->is_active,
            'archived_at' => $user->deleted_at?->toISOString(),
        ];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['is_active' => $message]);
    }
}
