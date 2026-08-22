<?php

namespace App\Http\Controllers\Admin\ActivityLogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLogs\IndexActivityLogRequest;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\Department;
use App\Models\User;
use App\Support\Application\ApplicationActivityLogger;
use App\Support\Department\DepartmentActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(IndexActivityLogRequest $request): View|RedirectResponse
    {
        $filters = $request->validated();

        $query = ActivityLog::query()
            ->with('actor')
            ->searchKeyword($filters['q'] ?? null)
            ->forActor(isset($filters['actor_id']) ? (int) $filters['actor_id'] : null)
            ->forAction($filters['action'] ?? null)
            ->forSubjectType($filters['subject_type'] ?? null)
            ->createdBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->latest('created_at')
            ->latest('id');

        $queryParameters = collect($filters)
            ->except('page')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();

        $activityLogs = $query->paginate(20)->appends($queryParameters);

        if ($activityLogs->total() > 0 && $activityLogs->currentPage() > $activityLogs->lastPage()) {
            return redirect()->route('admin.activity-logs.index', [
                ...$queryParameters,
                'page' => $activityLogs->lastPage(),
            ]);
        }

        $hasFilters = collect($queryParameters)->isNotEmpty();
        $actorOptions = $this->actorOptions();
        $actionOptions = $this->actionOptions();
        $subjectTypeOptions = $this->subjectTypeOptions();

        return view('admin.activity-logs.index', compact(
            'actionOptions',
            'activityLogs',
            'actorOptions',
            'filters',
            'hasFilters',
            'subjectTypeOptions',
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function actorOptions(): Collection
    {
        $actorIds = ActivityLog::query()
            ->whereNotNull('actor_id')
            ->distinct()
            ->pluck('actor_id');

        return User::withTrashed()
            ->whereIn('id', $actorIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'deleted_at']);
    }

    /**
     * @return array<string, string>
     */
    private function actionOptions(): array
    {
        $labels = [
            ApplicationActivityLogger::ASSIGNED => 'Phân công hồ sơ',
            ApplicationActivityLogger::CLAIMED => 'Nhận xử lý hồ sơ',
            ApplicationActivityLogger::PROCESSING_STARTED => 'Bắt đầu xử lý hồ sơ',
            ApplicationActivityLogger::SUPPLEMENT_REQUESTED => 'Yêu cầu bổ sung hồ sơ',
            ApplicationActivityLogger::PROCESSING_RESUMED => 'Tiếp tục xử lý hồ sơ',
            ApplicationActivityLogger::APPROVED => 'Duyệt hồ sơ',
            ApplicationActivityLogger::REJECTED => 'Từ chối hồ sơ',
            ApplicationActivityLogger::RESULT_DOCUMENT_UPLOADED => 'Đính kèm tài liệu kết quả',
            DepartmentActivityLogger::CREATED => 'Tạo phòng ban',
            DepartmentActivityLogger::UPDATED => 'Cập nhật phòng ban',
            DepartmentActivityLogger::ARCHIVED => 'Lưu trữ phòng ban',
            DepartmentActivityLogger::LEADER_CHANGED => 'Đổi lãnh đạo phòng ban',
            DepartmentActivityLogger::MEMBER_ADDED => 'Thêm thành viên phòng ban',
            DepartmentActivityLogger::MEMBER_REMOVED => 'Xóa thành viên phòng ban',
            DepartmentActivityLogger::MEMBER_TRANSFERRED => 'Chuyển thành viên phòng ban',
            'admin.login_succeeded' => 'Quản trị viên đăng nhập thành công',
            'admin.login_failed' => 'Quản trị viên đăng nhập thất bại',
            'admin.logout' => 'Quản trị viên đăng xuất',
            'access.denied' => 'Từ chối truy cập',
            'citizen.registered' => 'Công dân đăng ký',
            'citizen.login_succeeded' => 'Công dân đăng nhập thành công',
            'citizen.login_failed' => 'Công dân đăng nhập thất bại',
            'citizen.logout' => 'Công dân đăng xuất',
            'citizen.google_login_succeeded' => 'Công dân đăng nhập Google thành công',
            'citizen.google_login_failed' => 'Công dân đăng nhập Google thất bại',
            'citizen.google_registration_completed' => 'Công dân hoàn tất đăng ký Google',
            'profile.updated' => 'Cập nhật hồ sơ cá nhân',
            'user.activated' => 'Kích hoạt tài khoản',
            'user.deactivated' => 'Vô hiệu hóa tài khoản',
            'user.import.citizen' => 'Nhập CSV công dân',
            'user.import.staff' => 'Nhập CSV nhân viên',
            'user.export.citizens' => 'Xuất CSV công dân',
            'user.export.staff' => 'Xuất CSV nhân viên',
            'user.export.applications' => 'Xuất CSV hồ sơ',
            'user.export.services' => 'Xuất CSV dịch vụ',
            'user.export.departments' => 'Xuất CSV phòng ban',
        ];

        return ActivityLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->mapWithKeys(fn (string $action): array => [$action => $labels[$action] ?? $action])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function subjectTypeOptions(): array
    {
        $labels = [
            (new Application)->getMorphClass() => 'Hồ sơ',
            (new Department)->getMorphClass() => 'Phòng ban',
            (new User)->getMorphClass() => 'Người dùng',
        ];

        return ActivityLog::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $subjectType): array => [$subjectType => $labels[$subjectType] ?? class_basename($subjectType)])
            ->all();
    }
}
