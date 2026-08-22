<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Actions\Department\ArchiveDepartment;
use App\Actions\Department\CreateDepartment;
use App\Actions\Department\UpdateDepartment;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\ArchiveDepartmentRequest;
use App\Http\Requests\Admin\Departments\ListDepartmentsRequest;
use App\Http\Requests\Admin\Departments\StoreDepartmentRequest;
use App\Http\Requests\Admin\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(ListDepartmentsRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();
        $query = Department::withTrashed()->visibleTo($actor);

        $query->when($filters['status'] === 'active', fn (Builder $builder): Builder => $builder->whereNull('departments.deleted_at'))
            ->when($filters['status'] === 'archived', fn (Builder $builder): Builder => $builder->whereNotNull('departments.deleted_at'));

        if ($filters['manager_id'] ?? null) {
            $query->where('leader_id', (int) $filters['manager_id']);
        }

        if ($filters['search'] ?? null) {
            $pattern = '%'.$this->escapeLikePattern($filters['search']).'%';
            $query->where(function (Builder $searchQuery) use ($pattern): void {
                $searchQuery
                    ->whereRaw("name ILIKE ? ESCAPE E'\\\\'", [$pattern])
                    ->orWhereRaw("code ILIKE ? ESCAPE E'\\\\'", [$pattern])
                    ->orWhereRaw("address ILIKE ? ESCAPE E'\\\\'", [$pattern]);
            });
        }

        $departments = $query
            ->with('leader')
            ->withStructureCounts()
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
        $stats = $this->departmentStats($actor);
        $managers = $this->managerFilterOptions($actor);
        $hasFilters = ($filters['search'] ?? null) !== null
            || ($filters['manager_id'] ?? null) !== null
            || $filters['status'] !== 'active';

        return view('admin.departments.index', compact(
            'departments',
            'filters',
            'hasFilters',
            'managers',
            'stats',
        ));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        $selectedLeader = old('leader_id')
            ? User::query()->availableDepartmentLeaders()->find(old('leader_id'))
            : null;

        return view('admin.departments.create', compact('selectedLeader'));
    }

    public function store(
        StoreDepartmentRequest $request,
        CreateDepartment $createDepartment,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $department = $createDepartment->handle($request->validated(), $actor, $request);

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'Đã tạo phòng ban thành công.');
    }

    public function show(Department $department): View
    {
        $this->authorize('view', $department);

        $department->load([
            'leader',
            'members' => fn ($query) => $query->withTrashed()->orderBy('name')->orderBy('users.id'),
            'serviceTypes' => fn ($query) => $query->withTrashed()->orderBy('name')->orderBy('service_types.id'),
        ]);

        $leaderCandidates = Gate::allows('changeLeader', $department)
            ? User::query()
                ->availableDepartmentLeaders()
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'email'])
            : collect();
        $staffCandidates = Gate::allows('addMember', $department)
            ? User::query()
                ->availableDepartmentStaff()
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'email'])
            : collect();

        return view('admin.departments.show', compact('department', 'leaderCandidates', 'staffCandidates'));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('admin.departments.edit', compact('department'));
    }

    public function update(
        UpdateDepartmentRequest $request,
        Department $department,
        UpdateDepartment $updateDepartment,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updatedDepartment = $updateDepartment->handle(
            $department,
            $request->validated(),
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.show', $updatedDepartment)
            ->with('success', 'Đã cập nhật thông tin phòng ban.');
    }

    public function destroy(
        ArchiveDepartmentRequest $request,
        Department $department,
        ArchiveDepartment $archiveDepartment,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $archiveDepartment->handle(
            $department,
            (int) $request->validated('version'),
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Đã lưu trữ phòng ban. Thành viên, dịch vụ và lịch sử nghiệp vụ vẫn được giữ nguyên.');
    }

    /** @return array{total: int, active: int, missing_leader: int, staff_memberships: int} */
    private function departmentStats(User $actor): array
    {
        $scope = Department::withTrashed()->visibleTo($actor);
        $activeScope = (clone $scope)->whereNull('departments.deleted_at');

        $missingLeader = (clone $activeScope)
            ->where(function (Builder $query): void {
                $query->whereNull('leader_id')
                    ->orWhereDoesntHave('leader', fn (Builder $leaderQuery): Builder => $leaderQuery
                        ->where('role', UserRole::Manager->value)
                        ->where('is_active', true)
                        ->whereNull('users.deleted_at'));
            })
            ->count();

        $staffMemberships = DB::table('department_user')
            ->join('users', 'users.id', '=', 'department_user.user_id')
            ->where('users.role', UserRole::Staff->value)
            ->whereIn('department_user.department_id', (clone $activeScope)->select('departments.id'))
            ->count();

        return [
            'total' => (clone $scope)->count(),
            'active' => (clone $activeScope)->count(),
            'missing_leader' => $missingLeader,
            'staff_memberships' => $staffMemberships,
        ];
    }

    /** @return Collection<int, User> */
    private function managerFilterOptions(User $actor): Collection
    {
        $leaderIds = Department::withTrashed()
            ->visibleTo($actor)
            ->whereNotNull('leader_id')
            ->distinct()
            ->pluck('leader_id');

        return User::withTrashed()
            ->whereIn('id', $leaderIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'deleted_at']);
    }

    private function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
