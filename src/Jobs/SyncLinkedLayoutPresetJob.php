<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Jobs;

use Capell\LayoutBuilder\Actions\RunLinkedLayoutPresetSyncAction;
use Capell\LayoutBuilder\Enums\LayoutPresetSyncRunStatus;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SyncLinkedLayoutPresetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $runId) {}

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        $run = LayoutPresetSyncRun::query()->find($this->runId);

        return $run instanceof LayoutPresetSyncRun
            ? [new WithoutOverlapping('layout-preset:' . $run->preset_id)]
            : [];
    }

    public function handle(): void
    {
        $run = LayoutPresetSyncRun::query()->find($this->runId);

        if ($run instanceof LayoutPresetSyncRun) {
            RunLinkedLayoutPresetSyncAction::run($run);
        }
    }

    public function failed(Throwable $throwable): void
    {
        $run = LayoutPresetSyncRun::query()->find($this->runId);

        if ($run instanceof LayoutPresetSyncRun) {
            $run->forceFill([
                'status' => LayoutPresetSyncRunStatus::Failed,
                'summary' => ['error' => $throwable->getMessage()],
                'completed_at' => now(),
            ])->save();
        }
    }
}
