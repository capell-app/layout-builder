<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Jobs\ApplyLayoutBulkChangeRunJob;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Illuminate\Support\Carbon;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

final class QueueLayoutBulkChangeRunAction
{
    use AsAction;

    public function handle(LayoutBulkChangeRun $run, ?int $actorId = null): LayoutBulkChangeRun
    {
        if ($run->status === LayoutBulkChangeRunStatus::Blocked) {
            throw new LogicException('This bulk layout change is blocked by preview warnings and cannot be queued.');
        }

        $run->forceFill([
            'status' => LayoutBulkChangeRunStatus::Queued,
            'queued_by' => $actorId,
            'approved_by' => $actorId,
            'queued_at' => Carbon::now(),
            'approved_at' => Carbon::now(),
        ])->save();

        ApplyLayoutBulkChangeRunJob::dispatch((int) $run->getKey(), $actorId);

        return $run->refresh();
    }
}
