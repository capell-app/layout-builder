<?php

declare(strict_types=1);

namespace Capell\Hero\Console\Commands;

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Core\Console\Commands\Concerns\HasSitesOption;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Hero\Actions\AddHeroWidgetToLayoutAction;
use Capell\Hero\Actions\CreateHeroContentTypeAction;
use Capell\Hero\Actions\CreateHeroWidgetAction;
use Capell\Layout\Models\Widget;
use Capell\Layout\Support\Creator\DemoCreator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DemoCommand extends Command
{
    use HasSitesOption;

    protected $description = 'Inserts demo hero content into the selected site(s).';

    protected $signature = 'capell:hero-demo {--sites=}';

    private DemoCreator $demoCreator;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siteOptions = $this->resolveSiteOptions();
        $sites = $this->getSitesByNames($siteOptions);

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteOptions));

            return Command::FAILURE;
        }

        $this->demoCreator = resolve(DemoCreator::class);

        CreateHeroWidgetAction::run();

        $heroBannerWidget = CreateHeroWidgetAction::run(key: 'hero-banner', height: 'large');

        $sites->each(function (Site $site) use ($heroBannerWidget): void {
            $this->setupDemoHomepageWidget($site, $heroBannerWidget);

            if (CapellCore::hasPackage('capell-app/blog')) {
                $this->updateBlogHeroContent($site);
                $this->addHeroContentToArticlePages($site);
            }
        });

        $this->newLine();
        $this->info('Hero demo content inserted successfully.');

        return Command::SUCCESS;
    }

    private function resolveSiteOptions(): array
    {
        $sitesOption = $this->option('sites');
        if ($sitesOption) {
            if (is_string($sitesOption)) {
                return explode(',', $sitesOption);
            }

            if (is_array($sitesOption)) {
                return $sitesOption;
            }

            return [];
        }

        return $this->getDemoSites();
    }

    /**
     * @return Collection<int, Site>
     */
    private function getSitesByNames(array $siteNames): Collection
    {
        /** @var class-string<Site> $model */
        $model = CapellCore::getModel(ModelEnum::Site);

        return $model::query()
            ->with(['language', 'languages'])
            ->whereIn('name', $siteNames)
            ->get();
    }

    private function setupDemoHomepageWidget(Site $site, Widget $heroWidget): bool
    {
        $this->newLine();
        $this->line(sprintf('Selected site: %s', $site->name));

        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(ModelEnum::Page);

        $homepage = $model::getSiteHomePage($site);

        if ($homepage instanceof Page) {
            $homepage->loadMissing('layout');

            AddHeroWidgetToLayoutAction::run($heroWidget, $homepage->layout);

            $type = CreateHeroContentTypeAction::run();

            $this->demoCreator->createContentsWidget($heroWidget, $homepage, container: 'hero', type: $type);
        }

        $this->line('Demo hero content has been successfully created for site: ' . $site->name);

        return true;
    }

    private function updateBlogHeroContent(Site $site): void
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(ModelEnum::Page);

        $model::query()
            ->with('translations.language')
            ->where('site_id', $site->id)
            ->whereRelation('type', 'key', BlogPageTypeEnum::Blog->value)
            ->lazyById()
            ->each(function (Pageable $page): void {
                foreach ($page->translations as $translation) {
                    $meta = $translation->meta;
                    $meta['hero'] = '<h1>' . __('capell-blog::generic.latest_articles') . '</h1><p>' . __('capell-blog::generic.blog_intro') . '</p>';

                    $translation->update(['meta' => $meta]);
                }
            });
    }

    private function addHeroContentToArticlePages(Site $site): void
    {
        /** @var class-string<Article> $model */
        $model = CapellCore::getModel(BlogPageTypeEnum::Article);

        $model::query()
            ->with('translations.language')
            ->where('site_id', $site->id)
            ->whereRelation('type', 'key', BlogPageTypeEnum::Article->value)
            ->lazyById()
            ->each(function (Pageable $page): void {
                foreach ($page->translations as $translation) {
                    $meta = $translation->meta;
                    $meta['hero'] = '<h1>' . $translation->title . '</h1>';

                    $translation->update(['meta' => $meta]);
                }
            });
    }
}
