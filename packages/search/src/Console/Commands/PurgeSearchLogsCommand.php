<?php

declare(strict_types=1);

namespace Capell\Search\Console\Commands;

use Capell\Search\Actions\PurgeSearchLogsAction;
use Illuminate\Console\Command;

final class PurgeSearchLogsCommand extends Command
{
    protected $signature = 'search:purge {--days= : Override retention days}';

    protected $description = 'Delete old site search log records.';

    public function handle(): int
    {
        $retentionDays = $this->resolveRetentionDaysOption();
        $deletedCount = PurgeSearchLogsAction::run($retentionDays);

        $this->info(__('capell-search::actions.purged_logs', [
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
