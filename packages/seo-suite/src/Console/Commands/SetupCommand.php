<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Console\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Run post-install setup for Capell SEO Suite';

    protected $signature = 'capell:seo-suite-setup';

    public function handle(): int
    {
        $this->call('capell:xml-sitemap');

        $this->newLine();
        $this->info('Capell SEO Suite setup complete.');

        return Command::SUCCESS;
    }
}
