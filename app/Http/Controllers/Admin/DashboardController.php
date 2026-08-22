<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $completedStatuses = ApplicationStatus::completedValues();
        $completedPlaceholders = implode(', ', array_fill(0, count($completedStatuses), '?'));
        $overdueCondition = Application::overdueConditionSql();

        $aggregate = Application::query()
            ->visibleTo($actor)
            ->leftJoin('service_types', 'service_types.id', '=', 'applications.service_type_id')
            ->selectRaw(
                <<<SQL
                    COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE applications.status = ?) AS received,
                    COUNT(*) FILTER (WHERE applications.status = ?) AS assigned,
                    COUNT(*) FILTER (WHERE applications.status = ?) AS processing,
                    COUNT(*) FILTER (WHERE applications.status = ?) AS supplement_required,
                    COUNT(*) FILTER (WHERE applications.status = ?) AS pending_approval,
                    COUNT(*) FILTER (WHERE applications.status IN ({$completedPlaceholders})) AS completed,
                    COUNT(*) FILTER (
                        WHERE applications.status NOT IN ({$completedPlaceholders})
                        AND ({$overdueCondition})
                    ) AS overdue
                SQL,
                [
                    ApplicationStatus::Received->value,
                    ApplicationStatus::Assigned->value,
                    ApplicationStatus::Processing->value,
                    ApplicationStatus::SupplementRequired->value,
                    ApplicationStatus::PendingApproval->value,
                    ...$completedStatuses,
                    ...$completedStatuses,
                ],
            )
            ->firstOrFail();

        $metrics = collect([
            'total',
            'received',
            'assigned',
            'processing',
            'supplement_required',
            'pending_approval',
            'completed',
            'overdue',
        ])->mapWithKeys(fn (string $key): array => [$key => (int) $aggregate->getAttribute($key)])->all();

        // Staff có thêm số hồ sơ có thể nhận (claimable) — không nằm trong visibleTo nhưng vẫn thao tác được
        $claimableCount = 0;
        if ($actor->isStaff()) {
            $claimableCount = Application::query()->claimableBy($actor)->count();
        }

        $metricCards = [
            'total' => [
                'label' => 'Tổng hồ sơ',
                'description' => $actor->isStaff()
                    ? 'Tổng hồ sơ đã được gán cho bạn (chưa tính hồ sơ có thể nhận).'
                    : 'Tất cả hồ sơ trong phạm vi bạn được phép xem.',
                'url' => route('admin.applications.index'),
                'accent' => 'text-gray-950',
            ],
            'received' => [
                'label' => 'Mới tiếp nhận',
                'description' => 'Hồ sơ đã tiếp nhận và đang chờ phân công.',
                'url' => route('admin.applications.index', ['status' => ApplicationStatus::Received->value]),
                'accent' => 'text-sky-700',
            ],
            'assigned' => [
                'label' => 'Đã phân công',
                'description' => 'Hồ sơ đã phân công và chờ bắt đầu xử lý.',
                'url' => route('admin.applications.index', ['status' => ApplicationStatus::Assigned->value]),
                'accent' => 'text-indigo-700',
            ],
            'processing' => [
                'label' => 'Đang xử lý',
                'description' => 'Hồ sơ đang được cán bộ phụ trách xử lý.',
                'url' => route('admin.applications.index', ['status' => ApplicationStatus::Processing->value]),
                'accent' => 'text-blue-700',
            ],
            'supplement_required' => [
                'label' => 'Chờ bổ sung',
                'description' => 'Hồ sơ đang chờ công dân bổ sung thông tin hoặc tài liệu.',
                'url' => route('admin.applications.index', ['status' => ApplicationStatus::SupplementRequired->value]),
                'accent' => 'text-amber-700',
            ],
            'pending_approval' => [
                'label' => 'Chờ duyệt',
                'description' => 'Hồ sơ đã gửi chờ quản lý duyệt.',
                'url' => route('admin.applications.index', ['status' => ApplicationStatus::PendingApproval->value]),
                'accent' => 'text-purple-700',
            ],
            'completed' => [
                'label' => 'Đã hoàn thành',
                'description' => 'Tổng các hồ sơ đã duyệt hoặc đã từ chối.',
                'url' => route('admin.applications.index', ['status' => ApplicationStatus::COMPLETED_FILTER]),
                'accent' => 'text-emerald-700',
            ],
            'overdue' => [
                'label' => 'Quá hạn',
                'description' => 'Hồ sơ chưa hoàn thành và đã vượt thời gian xử lý của dịch vụ.',
                'url' => route('admin.applications.index', ['overdue' => 1]),
                'accent' => 'text-red-700',
            ],
        ];

        return view('admin.dashboard', compact('metricCards', 'metrics', 'claimableCount'));
    }
}
