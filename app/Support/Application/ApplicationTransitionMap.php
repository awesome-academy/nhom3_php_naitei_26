<?php

namespace App\Support\Application;

use App\Enums\ApplicationStatus;
use Illuminate\Validation\ValidationException;

final class ApplicationTransitionMap
{
    /**
     * @return array<string, list<ApplicationStatus>>
     */
    public static function allowedTransitions(): array
    {
        return [
            ApplicationStatus::Received->value => [
                ApplicationStatus::Assigned,
                ApplicationStatus::Processing,
            ],
            ApplicationStatus::Assigned->value => [
                ApplicationStatus::Processing,
            ],
            ApplicationStatus::Processing->value => [
                ApplicationStatus::SupplementRequired,
                ApplicationStatus::PendingApproval,
            ],
            ApplicationStatus::SupplementRequired->value => [
                ApplicationStatus::Processing,
            ],
            ApplicationStatus::PendingApproval->value => [
                ApplicationStatus::Approved,
                ApplicationStatus::Rejected,
                ApplicationStatus::Processing,
            ],
            ApplicationStatus::Approved->value => [],
            ApplicationStatus::Rejected->value => [],
        ];
    }

    public static function assertAllowed(ApplicationStatus $from, ApplicationStatus $to): void
    {
        $allowed = self::allowedTransitions()[$from->value] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Không thể chuyển trạng thái từ "%s" sang "%s".',
                    $from->value,
                    $to->value,
                ),
            ]);
        }
    }
}
