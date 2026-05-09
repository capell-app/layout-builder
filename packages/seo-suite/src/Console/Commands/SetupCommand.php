<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Console\Commands;

use Capell\SeoSuite\Actions\SeedDefaultAiCrawlerRulesAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Run post-install setup for Capell SEO Suite';

    protected $signature = 'capell:seo-suite-setup';

    public function handle(): int
    {
        SeedDefaultAiCrawlerRulesAction::run();

        $this->newLine();
        $this->info('Capell SEO Suite setup complete.');

        return Command::SUCCESS;
    }
}
