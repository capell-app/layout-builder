<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Data\DemoSitePlanData;
use Capell\LayoutBuilder\Support\Creator\ContentCreator;
use Capell\LayoutBuilder\Support\Creator\DemoCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(DemoSitePlanData $plan)
 */
class CreateLayoutBuilderDemoSiteAction
{
    use AsFake;
    use AsObject;

    private const NavigationPackage = 'capell-app/navigation';

    private DemoCreator $demoCreator;

    public function handle(DemoSitePlanData $plan): bool
    {
        $this->demoCreator = new DemoCreator(user: $plan->user);

        $typeCreator = resolve(TypeCreator::class);
        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        /** @var ContentCreator $contentCreator */
        $contentCreator = resolve(ContentCreator::class);

        $this->createSiteContents($contentCreator, $plan->contentTree, $plan->site);

        return $this->createDemoLayouts($plan->site);
    }

    private function createDemoLayouts(Site $site): bool
    {
        $languages = $site->languages;

        $homePage = $site->getHomePage();

        if (! $homePage instanceof Pageable) {
            return false;
        }

        $this->setupHomepage($homePage, $languages);

        $this->setupSiteNavigations($site, $languages, $homePage);

        return true;
    }

    private function setupHomepage(Pageable $page, EloquentCollection $languages): void
    {
        $layout = $this->getHomeLayout();
        throw_unless($layout instanceof Layout, Exception::class, 'Unable to find homepage layout');

        $page->update(['layout_id' => $layout->id]);

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $this->populateMainContainer($containers, $page);
        $this->populateFaqContainers($containers, $languages, $page);
        $this->populateSecondaryContainer($containers, $languages, $page);
        $this->populateAPWidgetsContainer($containers);
        $this->populateSplitTwoContainer($containers, $languages);
        $this->addSplitTwoBackgroundMedia($layout);

        $layout->update(['containers' => $containers]);
    }

    private function populateMainContainer(array &$containers, Pageable $page): void
    {
        $pageCardsWidget = $this->demoCreator->createPageCardsWidget($page);
        $galleryWidget = $this->demoCreator->createGalleryWidget();
        $secondPageCardsWidget = $this->demoCreator->createPageCardsWidget($page, occurrence: 2);
        $mediaCarouselWidget = $this->demoCreator->createMediaCarouselWidget();

        $containers['main']['widgets'] = [
            [
                'widget_key' => $pageCardsWidget->key,
                'occurrence' => 1,
            ],
            ['widget_key' => $galleryWidget->key],
            [
                'widget_key' => $secondPageCardsWidget->key,
                'occurrence' => 2,
            ],
            ['widget_key' => $mediaCarouselWidget->key],
        ];
    }

    private function populateFaqContainers(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $faqWidget = $this->demoCreator->createFaqWidget($languages);

        $containers['faq-main'] = [
            'meta' => [
                'colspan' => 8,
            ],
            'widgets' => [
                ['widget_key' => $faqWidget->key],
            ],
        ];

        $faqColumnWidget = $this->demoCreator->createStaticNavigationWidget($languages, $page->site);

        $containers['faq-col'] = [
            'meta' => [
                'colspan' => 4,
                'container' => ContainerWidthEnum::Full,
            ],
            'widgets' => [
                ['widget_key' => $faqColumnWidget->key],
            ],
        ];
    }

    private function populateSecondaryContainer(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $featureListWidget = $this->demoCreator->createModernFeatureListWidget();
        $teamPortfolioWidget = $this->demoCreator->createTeamPortfolioWidget($languages);
        $modernTeamWidget = $this->demoCreator->createModernTeamMembersWidget();
        $bannerImageWidget = $this->demoCreator->createBannerImageWidget($languages);
        $contentWidget = $this->demoCreator->createContentWidget($languages);
        $statisticsWidget = $this->demoCreator->createStatisticsWidget();
        $pricingWidget = $this->demoCreator->createModernPricingTableWidget();
        $businessFeaturesWidget = $this->demoCreator->createBusinessFeaturesWidget($page->site);
        $bannersWidget = $this->demoCreator->createBannersWidget();
        $clientLogosWidget = $this->demoCreator->createClientLogosWidget($languages);
        $testimonialsWidget = $this->demoCreator->createModernTestimonialsWidget();
        $faqWidget = $this->demoCreator->createModernFaqWidget();
        $statsWidget = $this->demoCreator->createModernStatsSectionWidget();
        $alternatingWidget = $this->demoCreator->createModernAlternatingContentWidget();
        $processWidget = $this->demoCreator->createModernProcessStepsWidget();
        $galleryWidget = $this->demoCreator->createModernImageGalleryWidget();

        $widgetCreator = resolve(WidgetCreator::class);

        $apHeroBannerWidget = $widgetCreator->apHeroBannerWidget();
        $apCardGridWidget = $widgetCreator->apCardGridWidget();
        $apFeatureListWidget = $widgetCreator->apFeatureListWidget();
        $apCtaSectionWidget = $widgetCreator->apCtaSectionWidget();
        $apImageGalleryWidget = $widgetCreator->apImageGalleryWidget();

        $containers['secondary'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'widgets' => [
                ['widget_key' => $featureListWidget->key],
                ['widget_key' => $teamPortfolioWidget->key],
                ['widget_key' => $modernTeamWidget->key],
                ['widget_key' => $bannerImageWidget->key],
                ['widget_key' => $contentWidget->key],
                ['widget_key' => $statisticsWidget->key],
                ['widget_key' => $pricingWidget->key],
                ['widget_key' => $businessFeaturesWidget->key],
                ['widget_key' => $bannersWidget->key],
                ['widget_key' => $clientLogosWidget->key],
                ['widget_key' => $testimonialsWidget->key],
                ['widget_key' => $faqWidget->key],
                ['widget_key' => $statsWidget->key],
                ['widget_key' => $alternatingWidget->key],
                ['widget_key' => $processWidget->key],
                ['widget_key' => $galleryWidget->key],
            ],
        ];

        $containers['ap-widgets'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'widgets' => [
                ['widget_key' => $apHeroBannerWidget->key],
                ['widget_key' => $apCardGridWidget->key],
                ['widget_key' => $apFeatureListWidget->key],
                ['widget_key' => $apCtaSectionWidget->key],
                ['widget_key' => $apImageGalleryWidget->key],
            ],
        ];
    }

    private function populateAPWidgetsContainer(array &$containers): void
    {
        $heroBannerWidget = $this->demoCreator->createApHeroBannerWidget();
        $cardGridWidget = $this->demoCreator->createApCardGridWidget();
        $featureListWidget = $this->demoCreator->createApFeatureListWidget();
        $ctaSectionWidget = $this->demoCreator->createApCtaSectionWidget();
        $imageGalleryWidget = $this->demoCreator->createApImageGalleryWidget();

        $containers['ap-widgets'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'widgets' => [
                ['widget_key' => $heroBannerWidget->key],
                ['widget_key' => $cardGridWidget->key],
                ['widget_key' => $featureListWidget->key],
                ['widget_key' => $ctaSectionWidget->key],
                ['widget_key' => $imageGalleryWidget->key],
            ],
        ];
    }

    private function populateSplitTwoContainer(array &$containers, EloquentCollection $languages): void
    {
        $splitContentWidget = $this->demoCreator->createSplitContentWidget($languages);

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
        $this->demoCreator->addSplitTwoBackgroundMedia($layout);
    }

    private function createSiteContents(
        ContentCreator $contentCreator,
        array $contentNode,
        Site $site,
        ?EloquentCollection $languages = null,
        ?Model $parent = null,
    ): void {
        if (Page::query()->where('site_id', $site->id)->count() > 28) {
            return;
        }

        $languages ??= $site->languages;

        $contentData = [
            'name' => $contentNode['name']['en'],
        ];

        if ($parent instanceof Model) {
            $contentData['parent_id'] = $parent->getKey();
        }

        foreach ($languages as $language) {
            $code = $language->getAttribute('code');
            $name = is_string($code) ? $contentNode['name'][$code] : null;

            if ($name === null) {
                continue;
            }

            $contentData['translations'][$code] = [
                'title' => $name,
                'content' => $name,
            ];
        }

        $content = $contentCreator->createContent($contentData, $site, $languages);

        if (! isset($contentNode['children'])) {
            return;
        }

        foreach ($contentNode['children'] as $childNode) {
            $this->createSiteContents($contentCreator, $childNode, $site, $languages, $content);
        }
    }

    private function setupSiteNavigations(Site $site, EloquentCollection $languages, Page $homePage): void
    {
        $navigationDemoCreatorClass = 'Capell\\Navigation\\Support\\Creator\\NavigationDemoCreator';

        if (! CapellCore::isPackageInstalled(self::NavigationPackage) || ! class_exists($navigationDemoCreatorClass)) {
            return;
        }

        $navigationDemoCreator = resolve($navigationDemoCreatorClass);

        $languages->each(function (Language $language) use ($navigationDemoCreator, $site, $homePage): void {
            $navigationDemoCreator->setupMainNavigation($site, $language, $homePage);
            $navigationDemoCreator->setupFooterNavigation($site, $language);
            $navigationDemoCreator->setupSubFooterNavigation($site, $language);
        });
    }

    private function getHomeLayout(): ?Layout
    {
        $layout = Layout::query()->firstWhere('key', LayoutEnum::Home);

        return $layout instanceof Layout ? $layout : null;
    }
}
