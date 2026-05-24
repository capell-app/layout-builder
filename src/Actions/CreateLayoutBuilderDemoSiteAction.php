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
use Capell\LayoutBuilder\Support\Creator\BlockCreator;
use Capell\LayoutBuilder\Support\Creator\ContentCreator;
use Capell\LayoutBuilder\Support\Creator\DemoCreator;
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

    /**
     * @param  EloquentCollection<int, Language>  $languages
     */
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
        $this->populateAPBlocksContainer($containers);
        $this->populateSplitTwoContainer($containers, $languages);
        $this->addSplitTwoBackgroundMedia($layout);

        $layout->update(['containers' => $containers]);
    }

    /**
     * @param  array<array-key, mixed>  $containers
     */
    private function populateMainContainer(array &$containers, Pageable $page): void
    {
        $pageCardsBlock = $this->demoCreator->createPageCardsBlock($page);
        $galleryBlock = $this->demoCreator->createGalleryBlock();
        $secondPageCardsBlock = $this->demoCreator->createPageCardsBlock($page, occurrence: 2);
        $mediaCarouselBlock = $this->demoCreator->createMediaCarouselBlock();

        $containers['main']['blocks'] = [
            [
                'block_key' => $pageCardsBlock->key,
                'occurrence' => 1,
            ],
            ['block_key' => $galleryBlock->key],
            [
                'block_key' => $secondPageCardsBlock->key,
                'occurrence' => 2,
            ],
            ['block_key' => $mediaCarouselBlock->key],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $containers
     * @param  EloquentCollection<int, Language>  $languages
     */
    private function populateFaqContainers(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $faqBlock = $this->demoCreator->createFaqBlock($languages);

        $containers['faq-main'] = [
            'meta' => [
                'colspan' => 8,
            ],
            'blocks' => [
                ['block_key' => $faqBlock->key],
            ],
        ];

        $faqColumnBlock = $this->demoCreator->createStaticNavigationBlock($languages, $page->site);

        $containers['faq-col'] = [
            'meta' => [
                'colspan' => 4,
                'container' => ContainerWidthEnum::Full,
            ],
            'blocks' => [
                ['block_key' => $faqColumnBlock->key],
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $containers
     * @param  EloquentCollection<int, Language>  $languages
     */
    private function populateSecondaryContainer(array &$containers, EloquentCollection $languages, Pageable $page): void
    {
        $featureListBlock = $this->demoCreator->createModernFeatureListBlock();
        $teamPortfolioBlock = $this->demoCreator->createTeamPortfolioBlock($languages);
        $modernTeamBlock = $this->demoCreator->createModernTeamMembersBlock();
        $bannerImageBlock = $this->demoCreator->createBannerImageBlock($languages);
        $contentBlock = $this->demoCreator->createContentBlock($languages);
        $statisticsBlock = $this->demoCreator->createStatisticsBlock();
        $pricingBlock = $this->demoCreator->createModernPricingTableBlock();
        $businessFeaturesBlock = $this->demoCreator->createBusinessFeaturesBlock($page->site);
        $bannersBlock = $this->demoCreator->createBannersBlock();
        $clientLogosBlock = $this->demoCreator->createClientLogosBlock($languages);
        $testimonialsBlock = $this->demoCreator->createModernTestimonialsBlock();
        $faqBlock = $this->demoCreator->createModernFaqBlock();
        $statsBlock = $this->demoCreator->createModernStatsSectionBlock();
        $alternatingBlock = $this->demoCreator->createModernAlternatingContentBlock();
        $processBlock = $this->demoCreator->createModernProcessStepsBlock();
        $galleryBlock = $this->demoCreator->createModernImageGalleryBlock();

        $blockCreator = resolve(BlockCreator::class);

        $apHeroBannerBlock = $blockCreator->apHeroBannerBlock();
        $apCardGridBlock = $blockCreator->apCardGridBlock();
        $apFeatureListBlock = $blockCreator->apFeatureListBlock();
        $apCtaSectionBlock = $blockCreator->apCtaSectionBlock();
        $apImageGalleryBlock = $blockCreator->apImageGalleryBlock();

        $containers['secondary'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'blocks' => [
                ['block_key' => $featureListBlock->key],
                ['block_key' => $teamPortfolioBlock->key],
                ['block_key' => $modernTeamBlock->key],
                ['block_key' => $bannerImageBlock->key],
                ['block_key' => $contentBlock->key],
                ['block_key' => $statisticsBlock->key],
                ['block_key' => $pricingBlock->key],
                ['block_key' => $businessFeaturesBlock->key],
                ['block_key' => $bannersBlock->key],
                ['block_key' => $clientLogosBlock->key],
                ['block_key' => $testimonialsBlock->key],
                ['block_key' => $faqBlock->key],
                ['block_key' => $statsBlock->key],
                ['block_key' => $alternatingBlock->key],
                ['block_key' => $processBlock->key],
                ['block_key' => $galleryBlock->key],
            ],
        ];

        $containers['ap-blocks'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'blocks' => [
                ['block_key' => $apHeroBannerBlock->key],
                ['block_key' => $apCardGridBlock->key],
                ['block_key' => $apFeatureListBlock->key],
                ['block_key' => $apCtaSectionBlock->key],
                ['block_key' => $apImageGalleryBlock->key],
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $containers
     */
    private function populateAPBlocksContainer(array &$containers): void
    {
        $heroBannerBlock = $this->demoCreator->createApHeroBannerBlock();
        $cardGridBlock = $this->demoCreator->createApCardGridBlock();
        $featureListBlock = $this->demoCreator->createApFeatureListBlock();
        $ctaSectionBlock = $this->demoCreator->createApCtaSectionBlock();
        $imageGalleryBlock = $this->demoCreator->createApImageGalleryBlock();

        $containers['ap-blocks'] = [
            'meta' => [
                'colspan' => 12,
            ],
            'blocks' => [
                ['block_key' => $heroBannerBlock->key],
                ['block_key' => $cardGridBlock->key],
                ['block_key' => $featureListBlock->key],
                ['block_key' => $ctaSectionBlock->key],
                ['block_key' => $imageGalleryBlock->key],
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $containers
     * @param  EloquentCollection<int, Language>  $languages
     */
    private function populateSplitTwoContainer(array &$containers, EloquentCollection $languages): void
    {
        $splitContentBlock = $this->demoCreator->createSplitContentBlock($languages);

        $containers['split-two'] = [
            'meta' => [
                'colspan' => 6,
                'column_start' => 7,
                'spacing' => 'none',
                'html_class' => 'relative',
                'background_color' => 'light-gray',
            ],
            'blocks' => [
                ['block_key' => $splitContentBlock->key],
            ],
        ];
    }

    private function addSplitTwoBackgroundMedia(Layout $layout): void
    {
        $this->demoCreator->addSplitTwoBackgroundMedia($layout);
    }

    /**
     * @param  array{name: array<string, string>, children?: array<int, array<string, mixed>>}  $contentNode
     * @param  EloquentCollection<int, Language>|null  $languages
     */
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

    /**
     * @param  EloquentCollection<int, Language>  $languages
     */
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
