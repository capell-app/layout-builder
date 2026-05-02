<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Console\Commands;

use Capell\SiteSearch\Actions\PurgeSiteSearchLogsAction;
use Illuminate\Console\Command;

final class PurgeSiteSearchLogsCommand extends Command
{
    protected $signature = 'site-search:purge {--days= : Override retention days}';

    protected $description = 'Delete old site search log records.';

    public function handle(): int
    {
        $retentionDays = $this->resolveRetentionDaysOption();
        $deletedCount = PurgeSiteSearchLogsAction::run($retentionDays);

        $this->info(__('capell-site-search::actions.purged_logs', [
            'count' => $deletedCount,
        ]));

        return self::SUCCESS;
    }

    private function resolveRetentionDaysOption(): ?int
    {
        $daysOption = $this->option('days');

        if ($daysOption === null || $daysOption === '') {
            return null;
        }

        return (int) $daysOption;
    }
}
