<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Enums\NavigationHandle;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Site $site)
 */
class CreateBlogPagesAction
{
    use AsFake;
    use AsObject;

    public function handle(Site $site): void
    {
        $blogCreator = resolve(BlogCreator::class);

        $archivesLayout = Layout::query()->firstWhere('key', 'archives');
        $resultsLayout = Layout::query()->firstWhere('key', 'results');

        $archivePageType = Type::query()->where('key', BlogPageTypeEnum::Archive)->pageType()->first();
        $blogPageType = Type::query()->where('key', BlogPageTypeEnum::Blog)->pageType()->first();
        $systemPageType = Type::query()->where('key', 'system')->pageType()->first();

        $blogPage = $blogCreator->createBlogPage($site, type: $blogPageType);

        $archivesPage = $blogCreator->createArchivesPage($blogPage, type: $systemPageType, layout: $archivesLayout);

        $blogCreator->createArchivePage($archivesPage, type: $archivePageType, layout: $resultsLayout);

        $blogCreator->addPagesToNavigations(
            [NavigationHandle::Main->value, NavigationHandle::Footer->value],
            site: $site,
            pages: [$blogPage],
            languages: $site->languages,
        );

        $tagsPage = $blogCreator->createTagsPage($site, $blogPage);

        $blogCreator->createTagPage($site, $tagsPage);
    }
}
