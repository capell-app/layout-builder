<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Jobs\ApplyLayoutBulkChangeRunJob;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Illuminate\Support\Facades\Date;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

final class QueueLayoutBulkChangeRunAction
{
    use AsAction;

    public function handle(LayoutBulkChangeRun $run, ?int $actorId = null): LayoutBulkChangeRun
    {
        throw_if($run->status === LayoutBulkChangeRunStatus::Blocked, LogicException::class, 'This bulk layout change is blocked by preview warnings and cannot be queued.');

        $run->forceFill([
            'status' => LayoutBulkChangeRunStatus::Queued,
            'queued_by' => $actorId,
            'approved_by' => $actorId,
            'queued_at' => Date::now(),
            'approved_at' => Date::now(),
        ])->save();

        dispatch(new ApplyLayoutBulkChangeRunJob((int) $run->getKey(), $actorId));

        return $run->refresh();
    }
}
