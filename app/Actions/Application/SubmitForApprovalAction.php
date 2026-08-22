<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;

class SubmitForApprovalAction extends TransitionsApplication
{
    protected function targetStatus(): ApplicationStatus
    {
        return ApplicationStatus::PendingApproval;
    }

    protected function ability(): string
    {
        return 'submitForApproval';
    }
}
