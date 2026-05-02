<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Models\SiteSearchLog;
use Lorisleiva\Actions\Concerns\AsAction;

final class PurgeSiteSearchLogsAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $resolvedRetentionDays = $retentionDays ?? (int) ResolveSiteSearchSettingAction::run(
            'log_retention_days',
            'capell-site-search.logs.retention_days',
            180,
        );

        return SiteSearchLog::query()
            ->where('searched_at', '<', now()->subDays($resolvedRetentionDays))
            ->delete();
    }
}
