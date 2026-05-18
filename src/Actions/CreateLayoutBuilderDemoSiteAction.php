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
use Capell\LayoutBuilder\Support\Creator\ElementCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;
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

    private const string NavigationPackage = 'capell-app/navigation';

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
        $this->populateAPElementsContainer($containers);
        $this->populateSplitTwoContainer($containers, $languages);
        $this->addSplitTwoBackgroundMedia($layout);

        $layout->update(['containers' => $containers]);
    }

    private function populateMainContainer(array &$containers, Pageable $page): void
    {
        $pageCardsElement = $this->demoCreator->createPageCardsElement($page);
        $galleryElement = $this->demoCreator->createGalleryElement();
        $secondPageCardsElement = $this->demoCreator->createPageCardsElement($page, occurrence: 2);
        $mediaCarouselElement = $this->demoCreator->createMediaCarouselElement();

        $containers['main']['elements'] = [
            [
                'element_key' => $pageCardsElement->key,
                'occurrence' => 1,
            ],
            ['element_key' => $galleryElement->key],
            [
                'element_key' => $secondPageCardsElement->key,
                'occurrence' => 2,
            ],
            ['element_key' => $mediaCarouselElement->key],
        ];
    }

    private function populateFaqContainers(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $faqElement = $this->demoCreator->createFaqElement($languages);

        $containers['faq-main'] = [
            'meta' => [
                'colspan' => 8,
            ],
            'elements' => [
                ['element_key' => $faqElement->key],
            ],
        ];

        $faqColumnElement = $this->demoCreator->createStaticNavigationElement($languages, $page->site);

        $containers['faq-col'] = [
            'meta' => [
                'colspan' => 4,
                'container' => ContainerWidthEnum::Full,
            ],
            'elements' => [
                ['element_key' => $faqColumnElement->key],
            ],
        ];
    }

    private function populateSecondaryContainer(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $featureListElement = $this->demoCreator->createModernFeatureListElement();
        $teamPortfolioElement = $this->demoCreator->createTeamPortfolioElement($languages);
        $modernTeamElement = $this->demoCreator->createModernTeamMembersElement();
        $bannerImageElement = $this->demoCreator->createBannerImageElement($languages);
        $contentElement = $this->demoCreator->createContentElement($languages);
        $statisticsElement = $this->demoCreator->createStatisticsElement();
        $pricingElement = $this->demoCreator->createModernPricingTableElement();
        $businessFeaturesElement = $this->demoCreator->createBusinessFeaturesElement($page->site);
        $bannersElement = $this->demoCreator->createBannersElement();
        $clientLogosElement = $this->demoCreator->createClientLogosElement($languages);
        $testimonialsElement = $this->demoCreator->createModernTestimonialsElement();
        $faqElement = $this->demoCreator->createModernFaqElement();
        $statsElement = $this->demoCreator->createModernStatsSectionElement();
        $alternatingElement = $this->demoCreator->createModernAlternatingContentElement();
        $processElement = $this->demoCreator->createModernProcessStepsElement();
        $galleryElement = $this->demoCreator->createModernImageGalleryElement();

        $elementCreator = resolve(ElementCreator::class);

        $apHeroBannerElement = $elementCreator->apHeroBannerElement();
        $apCardGridElement = $elementCreator->apCardGridElement();
        $apFeatureListElement = $elementCreator->apFeatureListElement();
        $apCtaSectionElement = $elementCreator->apCtaSectionElement();
        $apImageGalleryElement = $elementCreator->apImageGalleryElement();

        $containers['secondary'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'elements' => [
                ['element_key' => $featureListElement->key],
                ['element_key' => $teamPortfolioElement->key],
                ['element_key' => $modernTeamElement->key],
                ['element_key' => $bannerImageElement->key],
                ['element_key' => $contentElement->key],
                ['element_key' => $statisticsElement->key],
                ['element_key' => $pricingElement->key],
                ['element_key' => $businessFeaturesElement->key],
                ['element_key' => $bannersElement->key],
                ['element_key' => $clientLogosElement->key],
                ['element_key' => $testimonialsElement->key],
                ['element_key' => $faqElement->key],
                ['element_key' => $statsElement->key],
                ['element_key' => $alternatingElement->key],
                ['element_key' => $processElement->key],
                ['element_key' => $galleryElement->key],
            ],
        ];

        $containers['ap-elements'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'elements' => [
                ['element_key' => $apHeroBannerElement->key],
                ['element_key' => $apCardGridElement->key],
                ['element_key' => $apFeatureListElement->key],
                ['element_key' => $apCtaSectionElement->key],
                ['element_key' => $apImageGalleryElement->key],
            ],
        ];
    }

    private function populateAPElementsContainer(array &$containers): void
    {
        $heroBannerElement = $this->demoCreator->createApHeroBannerElement();
        $cardGridElement = $this->demoCreator->createApCardGridElement();
        $featureListElement = $this->demoCreator->createApFeatureListElement();
        $ctaSectionElement = $this->demoCreator->createApCtaSectionElement();
        $imageGalleryElement = $this->demoCreator->createApImageGalleryElement();

        $containers['ap-elements'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'elements' => [
                ['element_key' => $heroBannerElement->key],
                ['element_key' => $cardGridElement->key],
                ['element_key' => $featureListElement->key],
                ['element_key' => $ctaSectionElement->key],
                ['element_key' => $imageGalleryElement->key],
            ],
        ];
    }

    private function populateSplitTwoContainer(array &$containers, EloquentCollection $languages): void
    {
        $splitContentElement = $this->demoCreator->createSplitContentElement($languages);

        $containers['split-two'] = [
            'meta' => [
                'colspan' => 6,
                'column_start' => 7,
                'spacing' => 'none',
                'html_class' => 'relative',
                'background_color' => 'light-gray',
            ],
            'elements' => [
                ['element_key' => $splitContentElement->key],
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
        $navigationDemoCreatorClass = NavigationDemoCreator::class;

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
