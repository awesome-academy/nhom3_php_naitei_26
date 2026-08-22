<?php

namespace App\Support\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ApplicationWorkflowNotifier
{
    public function applicationSubmitted(Application $application, User $actor): void
    {
        if (! $actor->isCitizen()) {
            return;
        }

        $this->notifyCitizen($application, ApplicationStatusNotification::submitted($application));
    }

    public function statusChanged(
        Application $application,
        User $actor,
        ?ApplicationStatus $from,
        ApplicationStatus $to,
        ?string $note = null,
    ): void {
        $this->notifyCitizen($application, ApplicationStatusNotification::statusChanged($application, $to));
    }

    public function resultDocumentAvailable(Application $application, ApplicationDocument $document, User $actor): void
    {
        $this->notifyCitizen($application, ApplicationStatusNotification::resultDocumentAvailable($application, $document));
    }

    private function notifyCitizen(Application $application, ApplicationStatusNotification $notification): void
    {
        $application->loadMissing('citizen');

        $citizen = $application->citizen;

        if ($citizen === null || ! $citizen->isCitizen() || ! $citizen->canAccessProtectedResources()) {
            return;
        }

        try {
            $citizen->notify($notification);
        } catch (Throwable $exception) {
            Log::warning('Could not create application workflow notification.', [
                'application_id' => $application->getKey(),
                'citizen_id' => $citizen->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
