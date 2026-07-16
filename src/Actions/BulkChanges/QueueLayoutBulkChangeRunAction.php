<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Jobs\ApplyLayoutBulkChangeRunJob;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Illuminate\Support\Facades\Date;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static LayoutBulkChangeRun run(LayoutBulkChangeRun $run, ?int $actorId = null)
 */
final class QueueLayoutBulkChangeRunAction
{
    use AsFake;
    use AsObject;

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

        dispatch(new ApplyLayoutBulkChangeRunJob($this->runKey($run), $actorId));

        return $run->refresh();
    }

    private function runKey(LayoutBulkChangeRun $run): int
    {
        $key = $run->getKey();

        throw_unless(is_numeric($key), RuntimeException::class, 'Expected bulk change run key to be numeric.');

        return (int) $key;
    }
}
