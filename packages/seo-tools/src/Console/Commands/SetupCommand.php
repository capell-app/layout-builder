<?php

declare(strict_types=1);

namespace Capell\SeoTools\Console\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Run post-install setup for Capell SEO Tools';

    protected $signature = 'capell:seo-tools-setup';

    public function handle(): int
    {
        $this->call('capell:xml-sitemap');

        $this->newLine();
        $this->info('Capell SEO Tools setup complete.');

        return Command::SUCCESS;
    }
}
