<?php

declare(strict_types=1);

namespace Capell\Newsletter\Actions;

use Capell\Newsletter\Enums\SyncStatus;
use Capell\Newsletter\Jobs\SyncSubscriberToProviderJob;
use Capell\Newsletter\Models\SyncAttempt;
use Lorisleiva\Actions\Concerns\AsAction;

class RequeueDueProviderSyncAttemptsAction
{
    use AsAction;

    public function handle(?int $limit = null, bool $dispatchJobs = true): int
    {
        $query = SyncAttempt::query()
            ->where('sync_status', SyncStatus::RetryScheduled)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->oldest('next_retry_at');

        if (is_int($limit) && $limit > 0) {
            $query->limit($limit);
        }

        $count = 0;

        $query->each(function (SyncAttempt $syncAttempt) use (&$count, $dispatchJobs): void {
            $syncAttempt->forceFill([
                'sync_status' => SyncStatus::Pending,
                'next_retry_at' => null,
            ])->save();

            if ($dispatchJobs) {
                dispatch(new SyncSubscriberToProviderJob($syncAttempt));
            }

            $count++;
        });

        return $count;
    }
}
