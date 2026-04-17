<?php

declare(strict_types=1);

namespace Capell\Layout\Console\Commands;

use Capell\Core\Console\Commands\Concerns\HasSitesOption;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Models\Collection;
use Capell\Layout\Support\Creator\ContentCreator;
use Capell\Layout\Support\Creator\DemoCreator;
use Capell\Layout\Support\Creator\TypeCreator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\User;
use Symfony\Component\Console\Helper\ProgressBar;

class DemoCommand extends Command
{
    use HasSitesOption;

    protected $description = 'Inserts demo layout widgets';

    protected $signature = 'capell:layout-demo {--user} {--sites=}';

    protected DemoCreator $demoCreator;

    protected ?ProgressBar $progress = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siteOptions = $this->getSiteOptions();

        /** @var class-string<Site> $model */
        $model = CapellCore::getModel(ModelEnum::Site);

        $sites = $model::query()->with(['languages'])->whereIn('name', $siteOptions)->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', $siteOptions));

            return Command::FAILURE;
        }

        $user = $this->resolveUser();
        $this->demoCreator = new DemoCreator(user: $user);

        $data = config('capell-demo.pages');
        $typeCreator = resolve(TypeCreator::class);
        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        $sites->each(function (Site $site) use ($data): void {
            $this->newLine();
            $this->comment('Creating demo content for site: ' . $site->name);

            /** @var ContentCreator $contentCreator */
            $contentCreator = resolve(ContentCreator::class);

            $contentSteps = $this->countContentNodes($data[0]);
            $this->startProgress($contentSteps);
            $this->createSiteContents($contentCreator, $data[0], $site);
            $this->finishProgress();

            $this->newLine();
            $this->comment('Creating demo layouts');

            $this->createDemoLayouts($site);
        });

        $this->newLine();
        $this->info('Demo layouts have been successfully created.');

        return Command::SUCCESS;
    }

    public function createDemoLayouts(Site $site): bool
    {
        $languages = $site->languages;

        /** @var Page $home */
        $home = $site->getHomePage();

        if (! $home instanceof Pageable) {
            $this->error('Unable to find homepage for site: ' . $site->name);

            return false;
        }

        // main(4) + faq(2) + secondary(8) + split-two(1) + background(1)
        $totalSteps = 4 + 2 + 8 + 1 + 1;
        $this->startProgress($totalSteps);
        $this->setupHomepage($home, $languages);
        $this->finishProgress();

        return true;
    }

    public function setupHomepage(Pageable $page, EloquentCollection $languages): void
    {
        $layout = $this->getHomeLayout();
        throw_unless($layout instanceof Layout, Exception::class, 'Unable to find homepage layout');

        $page->update(['layout_id' => $layout->id]);

        $containers = $layout->containers ?? [];

        $this->populateMainContainer($containers, $page);
        $this->populateFaqContainers($containers, $languages, $page);
        $this->populateSecondaryContainer($containers, $languages, $page);
        $this->populateSplitTwoContainer($containers, $languages);
        $this->addSplitTwoBackgroundMedia($layout);

        $layout->containers = $containers;

        $layout->update(['containers' => $containers]);
    }

    private function getSiteOptions(): array
    {
        if ($this->option('sites')) {
            $sitesOption = $this->option('sites');
            if (is_string($sitesOption)) {
                return array_values(
                    array_filter(
                        array_map(trim(...), explode(',', $sitesOption)),
                        fn (string $siteOption): bool => $siteOption !== '',
                    ),
                );
            }

            if (is_array($sitesOption)) {
                return array_values(
                    array_filter(array_map(trim(...), $sitesOption), fn (string $siteOption): bool => $siteOption !== ''),
                );
            }

            return [];
        }

        return $this->getDemoSites();
    }

    private function resolveUser(): ?object
    {
        if ($this->option('user')) {
            /** @var class-string<User> $model */
            $model = CapellCore::getModel('User');

            return $model::query()->first();
        }

        if (auth()->check()) {
            return auth()->user();
        }

        return null;
    }

    private function populateMainContainer(array &$containers, Pageable $page): void
    {
        $this->setProgressMessage('Creating page cards widget');
        $pageCardsWidget = $this->demoCreator->createPageCardsWidget($page);
        $this->advanceProgress();

        $this->setProgressMessage('Creating gallery widget');
        $galleryWidget = $this->demoCreator->createGalleryWidget();
        $this->advanceProgress();

        $this->setProgressMessage('Creating page cards widget (#2)');
        $pageCardsWidget2 = $this->demoCreator->createPageCardsWidget($page, occurrence: 2);
        $this->advanceProgress();

        $this->setProgressMessage('Creating media carousel widget');
        $mediaCarouselWidget = $this->demoCreator->createMediaCarouselWidget();
        $this->advanceProgress();

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
    }

    private function populateFaqContainers(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $this->setProgressMessage('Creating FAQ widget');
        $faqWidget = $this->demoCreator->createFaqWidget($languages);
        $this->advanceProgress();

        $containers['faq-main'] = [
            'meta' => [
                'colspan' => 8,
            ],
            'widgets' => [
                ['widget_key' => $faqWidget->key],
            ],
        ];

        $this->setProgressMessage('Creating static navigation widget');
        $faqColWidget = $this->demoCreator->createStaticNavigationWidget($languages, $page->site);
        $this->advanceProgress();

        $containers['faq-col'] = [
            'meta' => [
                'colspan' => 4,
                'container' => ContainerWidthEnum::Full,
            ],
            'widgets' => [
                ['widget_key' => $faqColWidget->key],
            ],
        ];
    }

    private function populateSecondaryContainer(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $this->setProgressMessage('Creating team portfolio widget');
        $teamPortfolioWidget = $this->demoCreator->createTeamPortfolioWidget($languages);
        $this->advanceProgress();

        $this->setProgressMessage('Creating banner image widget');
        $bannerImageWidget = $this->demoCreator->createBannerImageWidget($languages);
        $this->advanceProgress();

        $this->setProgressMessage('Creating content widget');
        $contentWidget = $this->demoCreator->createContentWidget($languages);
        $this->advanceProgress();

        $this->setProgressMessage('Creating statistics blocks widget');
        $statisticsWidget = $this->demoCreator->createStatisticsWidget();
        $this->advanceProgress();

        $this->setProgressMessage('Creating business features widget');
        $businessFeaturesWidget = $this->demoCreator->createBusinessFeaturesWidget($page->site);
        $this->advanceProgress();

        $this->setProgressMessage('Creating banners widget');
        $bannersWidget = $this->demoCreator->createBannersWidget();
        $this->advanceProgress();

        $this->setProgressMessage('Creating client logos widget');
        $clientLogosWidget = $this->demoCreator->createClientLogosWidget($languages);
        $this->advanceProgress();

        $this->setProgressMessage('Creating testimonials widget');
        $testimonialsWidget = $this->demoCreator->createTestimonialsWidget($languages);
        $this->advanceProgress();

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
    }

    private function populateSplitTwoContainer(array &$containers, EloquentCollection $languages): void
    {
        $this->setProgressMessage('Creating split content widget');
        $splitContentWidget = $this->demoCreator->createSplitContentWidget($languages);
        $this->advanceProgress();

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
    }

    private function addSplitTwoBackgroundMedia(Layout $layout): void
    {
        $this->setProgressMessage('Adding split-two background media');
        if ($layout->getMedia('split-two-background')->isEmpty()) {
            resolve(\Capell\Core\Support\Creator\DemoCreator::class)->createMedia($layout, collection: 'split-two-background');
        }

        $this->advanceProgress();
    }

    private function createSiteContents(ContentCreator $contentCreator, array $data, Site $site, ?EloquentCollection $languages = null, ?Collection $parent = null): void
    {
        if ($site->contents()->count() > 28) {
            $this->setProgressMessage('Content limit reached.');

            return;
        }

        if (! $languages instanceof EloquentCollection) {
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

        $this->setProgressMessage('Creating content: ' . $contentData['name']);
        $content = $contentCreator->createContent($contentData, $site, $languages);
        $this->advanceProgress();

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
        $layout = $model::query()->firstWhere('key', LayoutEnum::Home);

        return $layout instanceof Layout ? $layout : null;
    }

    private function countContentNodes(array $data): int
    {
        $count = 1;
        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $child) {
                $count += $this->countContentNodes($child);
            }
        }

        return $count;
    }

    private function startProgress(int $max): void
    {
        $this->progress = $this->output->createProgressBar($max);
        $this->progress->setFormat(' [%bar%] %percent:3s%% | %message%');
        $this->progress->setMessage('');
        $this->progress->start();
    }

    private function setProgressMessage(string $message): void
    {
        if ($this->progress instanceof ProgressBar) {
            $this->progress->setMessage($message);
        }
    }

    private function advanceProgress(int $step = 1): void
    {
        if ($this->progress instanceof ProgressBar) {
            $this->progress->advance($step);
        }
    }

    private function finishProgress(): void
    {
        if ($this->progress instanceof ProgressBar) {
            $this->progress->finish();
            $this->newLine();
        }

        $this->progress = null;
    }
}
