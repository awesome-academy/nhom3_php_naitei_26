<?php

namespace App\Http\Controllers\Admin\Users;

use App\Actions\User\SetUserActiveStatus;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\ListUsersRequest;
use App\Http\Requests\Admin\Users\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /** @var list<string> */
    private const SAFE_COLUMNS = [
        'id',
        'name',
        'email',
        'role',
        'citizen_id',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'email_notifications_enabled',
        'is_active',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function index(ListUsersRequest $request): View|RedirectResponse
    {
        $filters = $request->validated();
        $query = User::query()
            ->select(self::SAFE_COLUMNS)
            ->withCount([
                'departments as departments_count' => fn ($departmentQuery) => $departmentQuery->withTrashed(),
                'ledDepartments as led_departments_count' => fn ($departmentQuery) => $departmentQuery->withTrashed(),
            ])
            ->when($filters['search'] ?? null, function (Builder $userQuery, string $search): void {
                $pattern = '%'.$this->escapeLikePattern($search).'%';

                $userQuery->where(function (Builder $searchQuery) use ($pattern): void {
                    $searchQuery
                        ->whereRaw("users.name COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                        ->orWhereRaw("users.email COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern])
                        ->orWhereRaw("users.citizen_id COLLATE \"und-x-icu\" ILIKE ? COLLATE \"und-x-icu\" ESCAPE E'\\\\'", [$pattern]);
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $userQuery, string $role): Builder => $userQuery->where('role', $role))
            ->when($filters['status'] ?? null, fn (Builder $userQuery, string $status): Builder => $userQuery->where('is_active', $status === 'active'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $queryParameters = collect($filters)
            ->except('page')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
        $users = $query->paginate(20)->appends($queryParameters);

        if ($users->total() > 0 && $users->currentPage() > $users->lastPage()) {
            return redirect()->route('admin.users.index', [
                ...$queryParameters,
                'page' => $users->lastPage(),
            ]);
        }

        $roleOptions = collect(UserRole::cases());
        $hasFilters = collect([
            $filters['search'] ?? null,
            $filters['role'] ?? null,
            $filters['status'] ?? null,
        ])->contains(fn (mixed $value): bool => $value !== null && $value !== '');

        return view('admin.users.index', compact('filters', 'hasFilters', 'roleOptions', 'users'));
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user = User::query()
            ->select(self::SAFE_COLUMNS)
            ->with([
                'departments' => fn ($query) => $query
                    ->withTrashed()
                    ->select(['departments.id', 'departments.name', 'departments.code', 'departments.deleted_at'])
                    ->orderBy('departments.name')
                    ->orderBy('departments.id'),
                'ledDepartments' => fn ($query) => $query
                    ->withTrashed()
                    ->select(['id', 'name', 'code', 'leader_id', 'deleted_at'])
                    ->orderBy('name')
                    ->orderBy('id'),
            ])
            ->withCount([
                'submittedApplications',
                'assignedApplications',
                'assignedApplications as unfinished_assigned_applications_count' => fn ($query) => $query
                    ->whereNotIn('status', ApplicationStatus::completedValues()),
            ])
            ->findOrFail($user->getKey());

        $deactivationBlockReason = $this->deactivationBlockReason($user, request()->user());

        return view('admin.users.show', compact('deactivationBlockReason', 'user'));
    }

    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user,
        SetUserActiveStatus $action,
    ): RedirectResponse {
        $desiredActive = $request->boolean('is_active');
        $action->handle($user, $desiredActive, $request->user(), $request);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $desiredActive
                ? 'Đã kích hoạt tài khoản người dùng.'
                : 'Đã vô hiệu hóa tài khoản người dùng.');
    }

    private function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function deactivationBlockReason(User $target, ?User $actor): ?string
    {
        if (! $target->is_active) {
            return null;
        }

        if ($actor?->is($target)) {
            return 'Bạn không thể vô hiệu hóa chính tài khoản đang đăng nhập.';
        }

        if ($target->isSuperAdmin() && User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->count() <= 1) {
            return 'Không thể vô hiệu hóa quản trị viên cấp cao đang hoạt động cuối cùng.';
        }

        if ($target->isManager() && $target->ledDepartments->contains(fn ($department): bool => ! $department->trashed())) {
            return 'Quản lý đang lãnh đạo một phòng ban hoạt động.';
        }

        if (($target->isStaff() || $target->isManager()) && $target->unfinished_assigned_applications_count > 0) {
            return 'Cán bộ đang phụ trách hồ sơ chưa hoàn thành.';
        }

        return null;
    }
}
