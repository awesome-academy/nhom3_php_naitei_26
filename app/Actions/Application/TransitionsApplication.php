<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use App\Support\Application\ApplicationTransitionMap;
use App\Support\Application\ApplicationWorkflowNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

abstract class TransitionsApplication
{
    public function __construct(
        private readonly ApplicationWorkflowNotifier $workflowNotifier,
    ) {}

    public function handle(Application $application, User $actor, ?string $note = null): Application
    {
        return DB::transaction(function () use ($application, $actor, $note): Application {
            $locked = Application::query()->lockForUpdate()->findOrFail($application->getKey());

            Gate::forUser($actor)->authorize($this->ability(), $locked);

            $from = $locked->status;
            $to = $this->targetStatus();

            ApplicationTransitionMap::assertAllowed($from, $to);

            $this->apply($locked, $actor, $note);

            $locked->status = $to;
            $locked->save();

            ApplicationStatusHistory::query()->create([
                'application_id' => $locked->getKey(),
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor->getKey(),
                'note' => $note,
            ]);

            $this->workflowNotifier->statusChanged($locked, $actor, $from, $to, $note);

            return $locked->refresh();
        });
    }

    abstract protected function targetStatus(): ApplicationStatus;

    abstract protected function ability(): string;

    protected function apply(Application $application, User $actor, ?string $note): void {}
}
