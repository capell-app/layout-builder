<?php

declare(strict_types=1);

namespace Capell\Insights\Console\Commands;

use Capell\Insights\Actions\PurgeInsightsDataAction;
use Illuminate\Console\Command;

final class PurgeInsightsDataCommand extends Command
{
    protected $signature = 'insights:purge {--days= : Override insights retention days}';

    protected $description = 'Delete old insights records.';

    public function handle(): int
    {
        $retentionDays = $this->resolveRetentionDaysOption();

        if ($retentionDays === 0) {
            return self::FAILURE;
        }

        $deletedRecords = PurgeInsightsDataAction::run($retentionDays);

        $this->info(sprintf('Purged %s insights records.', $deletedRecords));

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
