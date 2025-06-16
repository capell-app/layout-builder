<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Site $site)
 */
class CreateBlogPagesAction
{
    use AsCommand;
    use AsObject;

    public string $commandSignature = 'capell-blog:create-pages {site : The ID of the site to create blog pages for}';

    public function handle(Site $site): void
    {
        $archivesLayout = Layout::firstWhere('key', 'archives');
        $resultsLayout = Layout::firstWhere('key', 'results');

        $archivePageType = Type::where('key', 'archive')->pageType()->first();
        $blogPageType = Type::where('key', 'blog')->pageType()->first();
        $systemPageType = Type::where('key', 'system')->pageType()->first();

        $blogPage = BlogCreator::createBlogPage($site, $blogPageType, $resultsLayout, $site->languages);

        $archivesPage = BlogCreator::createArchivesPage($site, $blogPage, $systemPageType, $archivesLayout);

        BlogCreator::createArchivePage($site, $archivesPage, $archivePageType, $resultsLayout, $site->languages);

        BlogCreator::addPagesToNavigations(
            ['main', 'footer'],
            site: $site,
            pages: [$blogPage],
            languages: $site->languages
        );
    }

    public function asCommand(Command $command): void
    {
        $siteId = $command->argument('site');

        if (empty($siteId)) {
            $command->error('Site argument is required.');

            return;
        }

        $site = Site::find($siteId);

        if (! $site) {
            $command->error('Site not found with ID: '.$siteId);

            return;
        }

        $this->handle($site);

        $command->info('Blog pages created successfully for site: '.$site->name);
    }
}
