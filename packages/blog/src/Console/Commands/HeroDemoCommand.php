<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Models\Article;
use Capell\Core\LayoutBuilder\Actions\AddHeroWidgetToLayoutAction;
use Capell\Core\LayoutBuilder\Actions\CreateHeroWidgetAction;
use Capell\Core\LayoutBuilder\Support\Creator\DemoCreator;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class HeroDemoCommand extends Command
{
    protected $signature = 'capell:hero-demo {--sites=}';

    protected $description = 'Create demo hero content for selected blog sites.';

    public function handle(): int
    {
        $sites = $this->resolveSites();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any selected sites.');

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            $this->createHeroContentForSite($site);
            $this->info(sprintf('Demo hero content has been successfully created for site: %s', $site->name));
        }

        $this->info('Hero demo content inserted successfully.');

        return self::SUCCESS;
    }

    /** @return Collection<int, Site> */
    private function resolveSites(): Collection
    {
        $siteNames = $this->parseSiteNames();

        /** @var Collection<int, Site> $sites */
        $sites = Site::query()
            ->when(
                $siteNames !== [],
                fn (Builder $query): Builder => $query->whereIn('name', $siteNames),
            )
            ->get();

        return $sites;
    }

    /** @return list<string> */
    private function parseSiteNames(): array
    {
        $siteOption = $this->option('sites');

        if (is_string($siteOption) && $siteOption !== '') {
            return array_values(array_filter(array_map(
                trim(...),
                explode(',', $siteOption),
            ), static fn (string $siteName): bool => $siteName !== ''));
        }

        if (is_array($siteOption)) {
            return array_values(array_filter(array_map(
                fn (mixed $siteName): string => is_string($siteName) ? trim($siteName) : '',
                $siteOption,
            ), static fn (string $siteName): bool => $siteName !== ''));
        }

        return [];
    }

    private function createHeroContentForSite(Site $site): void
    {
        $blogPage = $this->blogPage($site);
        $blogHeroWidget = CreateHeroWidgetAction::run('blog-hero', __('capell-blog::generic.blog'));
        CreateHeroWidgetAction::run('article-hero', __('capell-blog::generic.article'));

        if ($blogPage instanceof Page && $blogPage->layout instanceof Layout) {
            AddHeroWidgetToLayoutAction::run($blogHeroWidget, $blogPage->layout);
            resolve(DemoCreator::class)->createContentsWidget($blogHeroWidget, $blogPage, 'hero');
            $this->applyBlogHeroMeta($blogPage);
        }

        $this->applyArticleHeroMeta($site);
    }

    private function blogPage(Site $site): ?Page
    {
        return Page::query()
            ->with(['layout', 'translations', 'type'])
            ->where('site_id', $site->id)
            ->whereRelation('type', 'key', 'blog')
            ->first();
    }

    private function applyBlogHeroMeta(Page $page): void
    {
        $hero = '<h1>' . __('capell-blog::generic.latest_articles') . '</h1><p>' . __('capell-blog::generic.blog_intro') . '</p>';

        $page->translations->each(fn (Translation $translation): bool => $this->mergeTranslationHero($translation, $hero));
    }

    private function applyArticleHeroMeta(Site $site): void
    {
        Article::query()
            ->with(['translations'])
            ->where('site_id', $site->id)
            ->get()
            ->each(function (Article $article): void {
                $article->translations->each(fn (Translation $translation): bool => $this->mergeTranslationHero($translation, '<h1>' . $translation->title . '</h1>'));
            });
    }

    private function mergeTranslationHero(Translation $translation, string $hero): bool
    {
        $translation->forceFill([
            'meta' => [
                ...($translation->meta ?? []),
                'hero' => $hero,
            ],
        ])->save();

        return true;
    }
}
