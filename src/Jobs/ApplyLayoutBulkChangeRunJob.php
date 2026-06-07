<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Jobs;

use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
}
