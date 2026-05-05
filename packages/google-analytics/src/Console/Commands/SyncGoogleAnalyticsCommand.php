<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Console\Commands;

use Capell\GoogleAnalytics\Actions\SyncGoogleAnalyticsMetricsAction;
use Illuminate\Console\Command;

final class SyncGoogleAnalyticsCommand extends Command
{
    protected $signature = 'google-analytics:sync';

    protected $description = 'Sync Google Analytics 4 metrics into local dashboard snapshots.';

    public function handle(): int
    {
        $result = SyncGoogleAnalyticsMetricsAction::run();

        $this->line($result->message);

        if (! $result->synced) {
            return self::SUCCESS;
        }

        $this->info(__('capell-google-analytics::sync.summary', [
            'daily' => $result->dailyRows,
            'pages' => $result->pageRows,
        ]));

        return self::SUCCESS;
    }
}
