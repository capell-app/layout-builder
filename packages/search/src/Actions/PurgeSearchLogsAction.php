<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Models\SearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class PurgeSearchLogsAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $resolvedRetentionDays = $retentionDays ?? (int) ResolveSearchSettingAction::run(
            'log_retention_days',
            'capell-search.logs.retention_days',
            180,
        );

        return SearchLog::query()
            ->where('searched_at', '<', now()->subDays($resolvedRetentionDays))
            ->delete();
    }
}
