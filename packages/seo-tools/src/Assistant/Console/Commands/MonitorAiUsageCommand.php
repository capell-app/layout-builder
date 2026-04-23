<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Console\Commands;

use Illuminate\Console\Command;

class MonitorAiUsageCommand extends Command
{
    protected $signature = 'capell:admin-monitor-ai-usage';

    protected $description = 'Monitor AI usage metrics';

    public function handle(): int
    {
        $this->info('AI usage monitoring not yet implemented.');

        return self::SUCCESS;
    }
}
