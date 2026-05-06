<?php

declare(strict_types=1);

namespace Capell\Blog\Actions;

use Capell\Blog\Data\BlogPublishingSurfaceData;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Core\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator as LayoutTypeCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Navigation\Enums\NavigationHandle;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static BlogPublishingSurfaceData run(Site $site, ?Collection $languages = null, bool $createWidgets = true)
 */
class EnsureBlogPublishingSurfaceAction
{
    use AsFake;
    use AsObject;

    public function handle(Site $site, ?Collection $languages = null, bool $createWidgets = true): BlogPublishingSurfaceData
    {
        $blogCreator = resolve(BlogCreator::class);
        $languages ??= $site->getAllLanguages();

        if ($createWidgets) {
            $this->ensureSurfaceWidgets($blogCreator, $languages);
        }

        $blogPage = $blogCreator->createBlogPage(
            $site,
            type: $this->getPageType($blogCreator, BlogPageTypeEnum::Blog->value),
            layout: $blogCreator->createBlogPageLayout(),
            languages: $languages,
        );

        $archivesPage = $blogCreator->createArchivesPage(
            $blogPage,
            type: $this->getPageType($blogCreator, PageTypeEnum::System->value),
            layout: $blogCreator->createArchivesLayout(),
            languages: $languages,
        );

        $archivePage = $blogCreator->createArchivePage(
            $archivesPage,
            type: $this->getPageType($blogCreator, BlogPageTypeEnum::Archive->value),
            layout: $this->getResultsLayout(),
            languages: $languages,
        );

        $tagsPage = $blogCreator->createTagsPage(
            $site,
            $blogPage,
            languages: $languages,
            type: $this->getPageType($blogCreator, PageTypeEnum::System->value),
            layout: $blogCreator->createTagsLayout(),
        );

        $tagPage = $blogCreator->createTagPage(
            $site,
            $tagsPage,
            languages: $languages,
            type: $this->getPageType($blogCreator, BlogPageTypeEnum::Tag->value),
            layout: $this->getResultsLayout(),
        );

        $blogCreator->addPagesToNavigations(
            [NavigationHandle::Main->value, NavigationHandle::Footer->value],
            site: $site,
            pages: [$blogPage],
            languages: $languages,
        );

        return new BlogPublishingSurfaceData(
            blogPage: $blogPage,
            archivesPage: $archivesPage,
            archivePage: $archivePage,
            tagsPage: $tagsPage,
            tagPage: $tagPage,
        );
    }

    private function ensureSurfaceWidgets(BlogCreator $blogCreator, Collection $languages): void
    {
        $resultsWidgetType = resolve(LayoutTypeCreator::class)->resultsWidgetType();

        $blogCreator->createLatestArticlesWidget($languages);
        $blogCreator->createArchivesWidget($languages);
        $blogCreator->createTagsWidget($languages);
        $blogCreator->relatedArticlesWidget($resultsWidgetType, $languages);

        resolve(WidgetCreator::class)->latestPagesWidget($resultsWidgetType, $languages);
    }

    private function getPageType(BlogCreator $blogCreator, string $key): Type
    {
        $type = Type::query()->where('key', $key)->pageType()->first();

        if ($type instanceof Type) {
            return $type;
        }

        return match ($key) {
            BlogPageTypeEnum::Archive->value => $blogCreator->createArchivePageType(),
            BlogPageTypeEnum::Blog->value => $blogCreator->createBlogPageType(),
            BlogPageTypeEnum::Tag->value => $blogCreator->createTagPageType(),
            PageTypeEnum::System->value => resolve(TypeCreator::class)->systemPageType(),
            default => resolve(TypeCreator::class)->createPageType($key),
        };
    }

    private function getResultsLayout(): Layout
    {
        $layout = Layout::query()->firstWhere('key', LayoutEnum::Results->value);

        if ($layout instanceof Layout) {
            return $layout;
        }

        return resolve(LayoutCreator::class)->create(LayoutEnum::Results);
    }
}
