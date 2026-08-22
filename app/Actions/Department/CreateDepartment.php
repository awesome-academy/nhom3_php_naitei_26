<?php

namespace App\Actions\Department;

use App\Models\Department;
use App\Models\User;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateDepartment
{
    public function __construct(private DepartmentActivityLogger $activityLogger) {}

    /**
     * @param  array{name: string, code: string, address: ?string, leader_id?: ?int}  $attributes
     */
    public function handle(array $attributes, User $actor, ?Request $request = null): Department
    {
        try {
            return DB::transaction(function () use ($attributes, $actor, $request): Department {
                $leader = null;
                $leaderId = $attributes['leader_id'] ?? null;
                if ($leaderId !== null) {
                    $leader = User::query()
                        ->availableDepartmentLeaders()
                        ->lockForUpdate()
                        ->find($leaderId);

                    if (! $leader) {
                        throw ValidationException::withMessages([
                            'leader_id' => 'Chỉ quản lý đang hoạt động và chưa được phân công vào phòng ban nào mới có thể làm lãnh đạo.',
                        ]);
                    }
                }

                $department = new Department;
                $department->fill([
                    'name' => $attributes['name'],
                    'code' => $attributes['code'],
                    'address' => $attributes['address'],
                    'leader_id' => $leader?->getKey(),
                ]);
                $department->lock_version = 0;
                $department->save();

                if ($leader) {
                    $department->users()->attach($leader->getKey());
                    $department->setRelation('leader', $leader);
                }

                $snapshot = $this->activityLogger->departmentSnapshot($department);
                $this->activityLogger->record(
                    DepartmentActivityLogger::CREATED,
                    $department,
                    $actor,
                    $request,
                    after: $snapshot,
                );

                return $department;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'code' => 'Mã phòng ban đã tồn tại, kể cả trong dữ liệu đã lưu trữ.',
            ]);
        }
    }
}
