<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Jobs;

use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class ApplyLayoutBulkChangeRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $runId,
        private readonly ?int $actorId = null,
    ) {}

    public function handle(): void
    {
        $run = LayoutBulkChangeRun::query()->find($this->runId);

        if (! $run instanceof LayoutBulkChangeRun) {
            return;
        }

        if (! $this->queuedActorMayApply($run)) {
            $run->forceFill([
                'status' => LayoutBulkChangeRunStatus::Failed,
                'summary' => [
                    ...($run->summary ?? []),
                    'error' => __('capell-layout-builder::message.bulk_change_actor_unauthorized'),
                ],
            ])->save();

            return;
        }

        ApplyLayoutBulkChangeRunAction::run($run, $this->actorId);
    }

    public function failed(Throwable $throwable): void
    {
        $run = LayoutBulkChangeRun::query()->find($this->runId);

        if ($run instanceof LayoutBulkChangeRun) {
            $run->forceFill([
                'status' => LayoutBulkChangeRunStatus::Failed,
                'summary' => [
                    'error' => $throwable->getMessage(),
                ],
            ])->save();
        }
    }

    private function queuedActorMayApply(LayoutBulkChangeRun $run): bool
    {
        if ($this->actorId === null) {
            return true;
        }

        if ((int) $run->queued_by !== $this->actorId) {
            return false;
        }

        $userModel = config('auth.providers.users.model');

        if (! is_string($userModel) || ! is_a($userModel, Model::class, true)) {
            return false;
        }

        $actor = $userModel::query()->find($this->actorId);

        return $actor instanceof Authenticatable
            && Gate::forUser($actor)->allows('Update:Layout');
    }
}
