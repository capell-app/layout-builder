<?php

declare(strict_types=1);

namespace Capell\Analytics\Console\Commands;

use Capell\Analytics\Actions\PurgeAnalyticsDataAction;
use Illuminate\Console\Command;

final class PurgeAnalyticsDataCommand extends Command
{
    protected $signature = 'analytics:purge {--days= : Override analytics retention days}';

    protected $description = 'Delete old analytics records.';

    public function handle(): int
    {
        $retentionDays = $this->resolveRetentionDaysOption();

        if ($retentionDays === 0) {
            return self::FAILURE;
        }

        $deletedRecords = PurgeAnalyticsDataAction::run($retentionDays);

        $this->info("Purged {$deletedRecords} analytics records.");

        return self::SUCCESS;
    }

    private function resolveRetentionDaysOption(): ?int
    {
        $daysOption = $this->option('days');

        if ($daysOption === null || $daysOption === '') {
            return null;
        }

        if (! is_string($daysOption) && ! is_int($daysOption)) {
            $this->error('The --days option must be a positive integer.');

            return 0;
        }

        $daysValue = (string) $daysOption;

        if (! ctype_digit($daysValue) || (int) $daysValue < 1) {
            $this->error('The --days option must be a positive integer.');

            return 0;
        }

        return (int) $daysValue;
    }
}
