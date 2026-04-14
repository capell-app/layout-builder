<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;

class CreateBlogPagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'capell:blog-create-pages {site : The ID of the site to create blog pages for}';

    /**
     * The console command description.
     */
    protected $description = 'Create blog-related pages (blog, archives, tags) for a specific site.';

    public function handle(): int
    {
        $siteId = $this->argument('site');

        if (blank($siteId)) {
            $this->error('Site argument is required.');

            return self::FAILURE;
        }

        /** @var Site|null $site */
        $site = Site::query()->find($siteId);

        if (! $site) {
            $this->error('Site not found with ID: ' . $siteId);

            return self::FAILURE;
        }

        CreateBlogPagesAction::run($site);

        $this->info('Blog pages created successfully for site: ' . $site->name);

        return self::SUCCESS;
    }
}
