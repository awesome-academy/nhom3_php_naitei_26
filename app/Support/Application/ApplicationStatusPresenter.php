<?php

namespace App\Support\Application;

use App\Enums\ApplicationStatus;

final class ApplicationStatusPresenter
{
    /**
     * @return array{label: string, description: string}
     */
    public static function status(ApplicationStatus $status): array
    {
        return [
            'label' => self::label($status),
            'description' => self::description($status),
        ];
    }

    public static function label(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Received => 'Đã tiếp nhận',
            ApplicationStatus::Processing => 'Đang xử lý',
            ApplicationStatus::SupplementRequired => 'Cần bổ sung',
            ApplicationStatus::Approved => 'Đã duyệt',
            ApplicationStatus::Rejected => 'Bị từ chối',
        };
    }

    public static function description(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Received => 'Hồ sơ đã được ghi nhận và đang chờ cán bộ kiểm tra.',
            ApplicationStatus::Processing => 'Hồ sơ đang được cán bộ phụ trách xử lý.',
            ApplicationStatus::SupplementRequired => 'Hồ sơ cần bổ sung thông tin hoặc tài liệu theo yêu cầu.',
            ApplicationStatus::Approved => 'Hồ sơ đã được duyệt và có thể có tài liệu kết quả.',
            ApplicationStatus::Rejected => 'Hồ sơ đã bị từ chối. Vui lòng xem lý do xử lý.',
        };
    }

    public static function transitionDescription(?ApplicationStatus $from, ApplicationStatus $to): string
    {
        if ($from === null) {
            return 'Hồ sơ được nộp thành công và bắt đầu quy trình xử lý.';
        }

        return match ($to) {
            ApplicationStatus::Received => 'Hồ sơ quay về trạng thái đã tiếp nhận.',
            ApplicationStatus::Processing => 'Hồ sơ được chuyển sang bước xử lý.',
            ApplicationStatus::SupplementRequired => 'Cán bộ yêu cầu bổ sung để tiếp tục xử lý hồ sơ.',
            ApplicationStatus::Approved => 'Hồ sơ được duyệt sau quá trình xử lý.',
            ApplicationStatus::Rejected => 'Hồ sơ bị từ chối sau quá trình xem xét.',
        };
    }
}
