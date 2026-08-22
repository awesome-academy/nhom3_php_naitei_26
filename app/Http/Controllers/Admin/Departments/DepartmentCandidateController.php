<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class DepartmentCandidateController extends Controller
{
    private const RESULT_LIMIT = 20;

    public function managerCandidates(Request $request): JsonResponse
    {
        $this->authorize('create', Department::class);
        $term = $this->validatedSearchTerm($request);

        $candidates = $this->searchUsers(User::query()->availableDepartmentLeaders(), $term)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT + 1)
            ->get(['id', 'name', 'email', 'role']);

        return $this->userCandidateResponse($candidates);
    }

    public function memberCandidates(Request $request, Department $department): JsonResponse
    {
        $this->authorize('addMember', $department);
        $term = $this->validatedSearchTerm($request);
        $candidates = $this->searchUsers(User::query()->availableDepartmentStaff(), $term)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT + 1)
            ->get(['id', 'name', 'email', 'role']);

        return $this->userCandidateResponse($candidates);
    }

    public function transferTargets(Request $request, Department $department, User $member): JsonResponse
    {
        $this->authorize('removeMember', $department);
        abort_unless($member->isStaff() && $member->canAccessProtectedResources(), 404);
        $term = $this->validatedSearchTerm($request);
        /** @var User $actor */
        $actor = $request->user();

        $query = Department::query()
            ->visibleTo($actor)
            ->whereKeyNot($department->getKey())
            ->whereDoesntHave('members', fn (Builder $memberQuery): Builder => $memberQuery
                ->whereKey($member->getKey()));
        $pattern = '%'.$this->escapeLikePattern($term).'%';
        $query->where(function (Builder $targetQuery) use ($pattern): void {
            $targetQuery
                ->whereRaw("name ILIKE ? ESCAPE E'\\\\'", [$pattern])
                ->orWhereRaw("code ILIKE ? ESCAPE E'\\\\'", [$pattern]);
        });

        $candidates = $query
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT + 1)
            ->get(['id', 'name', 'code', 'lock_version']);
        $hasMore = $candidates->count() > self::RESULT_LIMIT;
        $data = $candidates->take(self::RESULT_LIMIT)->map(fn (Department $target): array => [
            'id' => $target->getKey(),
            'name' => $target->name,
            'code' => $target->code,
            'version' => $target->lock_version,
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => ['has_more' => $hasMore],
        ]);
    }

    private function validatedSearchTerm(Request $request): string
    {
        $input = ['search' => trim((string) $request->query('search'))];

        return Validator::make($input, [
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'search.required' => 'Vui lòng nhập ít nhất 2 ký tự để tìm kiếm.',
            'search.min' => 'Vui lòng nhập ít nhất 2 ký tự để tìm kiếm.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
        ])->validate()['search'];
    }

    private function searchUsers(Builder $query, string $term): Builder
    {
        $pattern = '%'.$this->escapeLikePattern($term).'%';

        return $query->where(function (Builder $candidateQuery) use ($pattern): void {
            $candidateQuery
                ->whereRaw("name ILIKE ? ESCAPE E'\\\\'", [$pattern])
                ->orWhereRaw("email ILIKE ? ESCAPE E'\\\\'", [$pattern]);
        });
    }

    private function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param  Collection<int, User>  $candidates
     */
    private function userCandidateResponse(Collection $candidates): JsonResponse
    {
        $hasMore = $candidates->count() > self::RESULT_LIMIT;
        $data = $candidates->take(self::RESULT_LIMIT)->map(fn (User $user): array => [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => ['has_more' => $hasMore],
        ]);
    }
}
