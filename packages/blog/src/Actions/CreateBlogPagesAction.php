<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Services\BlogCreator;
use Capell\Core\Enums\NavigationHandle;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Site $site)
 */
class CreateBlogPagesAction
{
    use AsObject;

    public function handle(Site $site): void
    {
        $archivesLayout = Layout::query()->firstWhere('key', 'archives');
        $resultsLayout = Layout::query()->firstWhere('key', 'results');

        $archivePageType = Type::query()->where('key', 'archive')->pageType()->first();
        $blogPageType = Type::query()->where('key', 'blog')->pageType()->first();
        $systemPageType = Type::query()->where('key', 'system')->pageType()->first();

        $blogCreator = app(BlogCreator::class);

        $blogPage = $blogCreator->createBlogPage($site, type: $blogPageType, languages: $site->languages);

        $archivesPage = $blogCreator->createArchivesPage($site, $blogPage, type: $systemPageType, layout: $archivesLayout);

        $blogCreator->createArchivePage($site, $archivesPage, type: $archivePageType, layout: $resultsLayout, languages: $site->languages);

        $blogCreator->addPagesToNavigations(
            [NavigationHandle::Main->value, NavigationHandle::Footer->value],
            site: $site,
            pages: [$blogPage],
            languages: $site->languages,
        );

        $tagsPage = $blogCreator->createTagsPage($site, $site->languages);

        $blogCreator->createTagPage($site, $tagsPage, $site->languages);
    }
}
