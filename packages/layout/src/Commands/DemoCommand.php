<?php

declare(strict_types=1);

namespace Capell\Layout\Commands;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Actions\CreateThemeAction;
use Capell\Layout\Enums\LayoutEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Services\Creator\ContentCreator;
use Capell\Layout\Services\Creator\DemoCreator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use function Laravel\Prompts\multisearch;

class DemoCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts demo layout widgets';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-layout:demo {--author} {--sites=}';

    protected DemoCreator $demoCreator;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sites')) {
            $siteIds = is_string($this->option('sites'))
                ? [$this->option('sites')]
                : $this->option('sites');
        } else {
            $sites = CapellCore::getModel(ModelEnum::Site)::query()
                ->limit(10)
                ->select(['id', 'name']);

            if ($sites->count() === 1) {
                $siteIds = $sites->pluck('id')->toArray();
            } else {
                $siteIds = multisearch(
                    'Select a site to insert demo pages',
                    options: fn (string $search) => $sites->when(
                        mb_strlen($search) > 0,
                        fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
                    )
                        ->get()
                        ->mapWithKeys(fn (Site $site) => [$site->id => $site->name])
                        ->toArray(),
                    validate: [
                        'required',
                        'array',
                        'min:1',
                    ],
                );
            }
        }

        $user = $this->option('author') ? CapellCore::getModel('User')::first() : null;

        $this->demoCreator = new DemoCreator(user: $user);

        $demo_data = config('capell-demo.pages');

        $contentCreator = app(ContentCreator::class);
        $contentCreator->createContentTypes();

        $sites = Site::whereIn('id', $siteIds)->get();

        if ($sites->isEmpty()) {
            throw new Exception('Unable to find any sites');
        }

        foreach ($sites as $site) {
            $this->info(sprintf('Selected site: %s', $site->name));

            $this->line('Associating theme with site: '.$site->name);

            $theme = CreateThemeAction::run();

            $site->update(['theme_id' => $theme->id]);

            $this->line('Setting up content');

            /** @var ContentCreator $contentCreator */
            $contentCreator = app(ContentCreator::class);

            $this->createSiteContents($contentCreator, $demo_data[0], $site);

            if (! $this->createDemoLayouts($site)) {
                $this->error('Failed to create demo pages for the selected site.');

                return Command::FAILURE;
            }
        }

        $this->info('Demo layouts have been successfully created.');

        return Command::SUCCESS;
    }

    public function createDemoLayouts(Site $site): bool
    {

        $this->newLine();
        $this->line('Setting up homepage extras for site: '.$site->name);

        $languages = $site->languages;

        $homePage = $site->pages()->isHomePage()->first();

        $this->setupHomepage($homePage, $languages);

        return true;
    }

    public function setupHomepage(Page $page, Collection $languages): void
    {
        $layout = $this->getHomeLayout();
        if (! $layout instanceof Layout) {
            throw new Exception('Unable to find homepage layout');
        }

        $page->update(['layout_id' => $layout->id]);

        $containers = $layout->containers;

        $heroWidget = $this->demoCreator->createHeroWidget();

        $this->demoCreator->createWidgetAssets($heroWidget, $page, container: 'hero');

        $containers = ['hero' => [
            'meta' => [
                'colspan' => 12,
                'container' => 'full',
            ],
            'widgets' => [
                [
                    'widget_key' => $heroWidget->key,
                    'occurrence' => 1,
                ],
            ],
        ]] + $containers;

        $containers['main']['widgets'] = [
            [
                'widget_key' => $this->demoCreator->createPageCardsWidget($page)->key,
                'occurrence' => 1,
            ],
            ['widget_key' => $this->demoCreator->createGalleryWidget()->key],
            [
                'widget_key' => $this->demoCreator->createPageCardsWidget($page, occurrence: 2)->key,
                'occurrence' => 2,
            ],
            ['widget_key' => $this->demoCreator->createMediaCarouselWidget()->key],
        ];

        $containers['faq-main'] = [
            'meta' => [
                'colspan' => 8,
            ],
            'widgets' => [
                ['widget_key' => $this->demoCreator->createFaqWidget($languages)->key],
            ],
        ];

        $containers['faq-col'] = [
            'meta' => [
                'colspan' => 4,
                'container' => 'full',
            ],
            'widgets' => [
                ['widget_key' => $this->demoCreator->createStaticNavigationWidget($languages, $page->site)->key],
            ],
        ];

        $containers['secondary'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'widgets' => [
                ['widget_key' => $this->demoCreator->createTeamPortfolioWidget($languages)->key],
                ['widget_key' => $this->demoCreator->createBannerImageWidget($languages)->key],
                ['widget_key' => $this->demoCreator->createContentWidget($languages)->key],
                ['widget_key' => $this->demoCreator->createStatisticsWidget()->key],
                ['widget_key' => $this->demoCreator->createBusinessFeaturesWidget($page->site)->key],
                ['widget_key' => $this->demoCreator->createBannersWidget()->key],
                ['widget_key' => $this->demoCreator->createClientLogosWidget($languages)->key],
                ['widget_key' => $this->demoCreator->createTestimonialsWidget($languages)->key],
            ],
        ];

        $containers['split-two'] = [
            'meta' => [
                'colspan' => 6,
                'column_start' => 7,
                'spacing' => 'none',
                'html_class' => 'relative',
                'background_color' => 'light-gray',
                'background_image_id' => $this->demoCreator->getExampleMedia()?->id,
            ],
            'widgets' => [
                ['widget_key' => $this->demoCreator->createSplitContentWidget($languages)->key],
            ],
        ];

        $layout->containers = $containers;
        $layout->update(['containers' => $containers]);
    }

    private function createSiteContents(ContentCreator $contentCreator, array $data, Site $site, ?Collection $languages = null, ?Content $parent = null): void
    {
        if ($site->contents()->count() > 28) {
            return;
        }

        if (! $languages instanceof Collection) {
            $languages = $site->languages;
        }

        $contentData = [
            'name' => $data['name']['en'],
        ];

        if ($parent instanceof Content) {
            $contentData['parent_id'] = $parent->id;
        }

        foreach ($languages as $language) {
            $name = $data['name'][$language->code];

            $contentData['translations'][$language->code] = [
                'title' => $name,
                'contents' => $name,
            ];
        }

        $content = $contentCreator->createContent($contentData, $site, $languages);

        if (! isset($data['children'])) {
            return;
        }

        foreach ($data['children'] as $child) {
            $this->createSiteContents($contentCreator, $child, $site, $languages, $content);
        }
    }

    private function getHomeLayout(): ?Layout
    {
        $model = CapellCore::getModel(ModelEnum::Layout);

        return $model::firstWhere('key', LayoutEnum::Home);
    }
}
