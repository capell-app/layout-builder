<?php

declare(strict_types=1);

namespace Capell\Layout\Commands;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Models\Content;
use Capell\Layout\Services\Creator\ContentCreator;
use Capell\Layout\Services\Creator\DemoCreator;
use Capell\Layout\Services\Creator\TypeCreator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class DemoCommand extends Command
{
    use HasSitesOption;

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
            $siteOptions = is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : null);
        } else {
            $siteOptions = $this->getSelectedSites();
        }

        $sites = CapellCore::getModel(ModelEnum::Site)::query()->with(['languages'])->whereIn('name', $siteOptions)->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', (array) $siteOptions));

            return Command::FAILURE;
        }

        $user = $this->option('author') ? CapellCore::getModel('User')::first() : null;

        $this->demoCreator = new DemoCreator(user: $user);

        $data = config('capell-demo.pages');

        $typeCreator = app(TypeCreator::class);
        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        foreach ($sites as $site) {
            $this->newLine();
            $this->info(sprintf('Selected site: %s', $site->name));

            $this->line('Setting up content');

            /** @var ContentCreator $contentCreator */
            $contentCreator = app(ContentCreator::class);

            // $this->createSiteContents($contentCreator, $data[0], $site);

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
        $this->line('Setting up homepage extras for site: ' . $site->name);
        $this->newLine();

        $languages = $site->languages;

        $homePage = $site->pages()->homePage()->first();

        $this->setupHomepage($homePage, $languages);

        return true;
    }

    public function setupHomepage(Page $page, Collection $languages): void
    {
        $layout = $this->getHomeLayout();
        throw_unless($layout instanceof Layout, new Exception('Unable to find homepage layout'));

        $page->update(['layout_id' => $layout->id]);

        $containers = $layout->containers;

        $pageCardsWidget = $this->demoCreator->createPageCardsWidget($page);
        $this->line('Created page cards widgets');

        $galleryWidget = $this->demoCreator->createGalleryWidget();
        $this->line('Created gallery widget');

        $pageCardsWidget2 = $this->demoCreator->createPageCardsWidget($page, occurrence: 2);
        $this->line('Created page cards widgets');

        $mediaCarouselWidget = $this->demoCreator->createMediaCarouselWidget();
        $this->line('Created media carousel widget');

        $containers['main']['widgets'] = [
            [
                'widget_key' => $pageCardsWidget->key,
                'occurrence' => 1,
            ],
            ['widget_key' => $galleryWidget->key],
            [
                'widget_key' => $pageCardsWidget2->key,
                'occurrence' => 2,
            ],
            ['widget_key' => $mediaCarouselWidget->key],
        ];

        $faqWidget = $this->demoCreator->createFaqWidget($languages);
        $this->line('Created FAQ widget');

        $containers['faq-main'] = [
            'meta' => [
                'colspan' => 8,
            ],
            'widgets' => [
                ['widget_key' => $faqWidget->key],
            ],
        ];

        $faqColWidget = $this->demoCreator->createStaticNavigationWidget($languages, $page->site);
        $this->line('Created static FAQ widget: ' . $faqColWidget->key);

        $containers['faq-col'] = [
            'meta' => [
                'colspan' => 4,
                'container' => 'full',
            ],
            'widgets' => [
                ['widget_key' => $faqColWidget->key],
            ],
        ];

        $teamPortfolioWidget = $this->demoCreator->createTeamPortfolioWidget($languages);
        $this->line('Created team portfolio widget: ' . $teamPortfolioWidget->key);

        $bannerImageWidget = $this->demoCreator->createBannerImageWidget($languages);
        $this->line('Created banner image widget: ' . $bannerImageWidget->key);

        $contentWidget = $this->demoCreator->createContentWidget($languages);
        $this->line('Created content widget: ' . $contentWidget->key);

        $statisticsWidget = $this->demoCreator->createStatisticsWidget();
        $this->line('Created statistics widget: ' . $statisticsWidget->key);

        $businessFeaturesWidget = $this->demoCreator->createBusinessFeaturesWidget($page->site);
        $this->line('Created business features widget: ' . $businessFeaturesWidget->key);

        $bannersWidget = $this->demoCreator->createBannersWidget();
        $this->line('Created banners widget: ' . $bannersWidget->key);

        $clientLogosWidget = $this->demoCreator->createClientLogosWidget($languages);
        $this->line('Created client logos widget: ' . $clientLogosWidget->key);

        $testimonialsWidget = $this->demoCreator->createTestimonialsWidget($languages);
        $this->line('Created testimonials widget: ' . $testimonialsWidget->key);

        $containers['secondary'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'widgets' => [
                ['widget_key' => $teamPortfolioWidget->key],
                ['widget_key' => $bannerImageWidget->key],
                ['widget_key' => $contentWidget->key],
                ['widget_key' => $statisticsWidget->key],
                ['widget_key' => $businessFeaturesWidget->key],
                ['widget_key' => $bannersWidget->key],
                ['widget_key' => $clientLogosWidget->key],
                ['widget_key' => $testimonialsWidget->key],
            ],
        ];

        $splitContentWidget = $this->demoCreator->createSplitContentWidget($languages);
        $this->line('Created split content widget: ' . $splitContentWidget->key);

        $containers['split-two'] = [
            'meta' => [
                'colspan' => 6,
                'column_start' => 7,
                'spacing' => 'none',
                'html_class' => 'relative',
                'background_color' => 'light-gray',
            ],
            'widgets' => [
                ['widget_key' => $splitContentWidget->key],
            ],
        ];

        $this->line('Adding background media to split container');
        if ($layout->getMedia('split-two-background')->isEmpty()) {
            app(\Capell\Admin\Services\Creator\DemoCreator::class)->createMedia($layout, collection: 'split-two-background');
        }

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
                'content' => $name,
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
