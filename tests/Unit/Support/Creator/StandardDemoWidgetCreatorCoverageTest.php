<?php

declare(strict_types=1);

use Capell\Core\Data\PageTypeData;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderStandardDemoContentPage;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderStandardDemoWidgetCreatorHarness;
use Illuminate\Support\Collection;

/**
 * @return array<array-key, mixed>
 */
function prepareStandardDemoCreatorHarness(Language $language): array
{
    if (! CapellCore::hasPageType('section')) {
        CapellCore::registerPageType(new PageTypeData(
            name: 'section',
            model: LayoutBuilderStandardDemoContentPage::class,
            label: 'Section',
        ));
    }

    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();

    resolve(TypeCreator::class)->createWidgetTypes();
    resolve(TypeCreator::class)->createDefaultContentType();
    resolve(TypeCreator::class)->createBuilderContentType();

    $defaultPageType = resolve(BlueprintCreator::class)->defaultPageType();
    $layout = Layout::factory()->default()->create();

    LayoutBuilderStandardDemoContentPage::$defaultSiteId = $site->getKey();
    LayoutBuilderStandardDemoContentPage::$defaultLayoutId = $layout->getKey();
    LayoutBuilderStandardDemoContentPage::$defaultBlueprintId = $defaultPageType->getKey();

    return [$site, $layout, $defaultPageType, new LayoutBuilderStandardDemoWidgetCreatorHarness];
}

it('builds standard content, faq, gallery, carousel, and navigation demo widgets', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [$site, $layout, $defaultPageType, $creator] = prepareStandardDemoCreatorHarness($language);

    $homePage = Page::factory()
        ->home()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()]);

    Page::factory()
        ->count(3)
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()])
        ->each(function (Page $page): void {
            Media::factory()->model($page)->collection(MediaCollectionEnum::Image)->create();
        });

    $languages = new Collection([$language]);

    $contentWidget = $creator->createContentWidget($languages);
    $splitContentWidget = $creator->createSplitContentWidget($languages);
    $faqWidget = $creator->createFaqWidget($languages);
    $galleryWidget = $creator->createGalleryWidget();
    $carouselWidget = $creator->createMediaCarouselWidget();
    $navigationWidget = $creator->createStaticNavigationWidget($languages, $site);
    $pageCardsWidget = $creator->createPageCardsWidget($homePage, occurrence: 3);

    expect($contentWidget->key)->toBe('example-content')
        ->and($splitContentWidget->key)->toBe('example-split-content')
        ->and($faqWidget->key)->toBe('faq')
        ->and($faqWidget->assets()->count())->toBe(6)
        ->and($galleryWidget->assets()->count())->toBe(5)
        ->and($carouselWidget->assets()->count())->toBe(8)
        ->and($navigationWidget->meta['navigation'])->toBe('example-menu')
        ->and($pageCardsWidget->assets()->where('occurrence', 3)->count())->toBe(3);
});

it('creates faq widgets for languages without explicit demo questions', function (): void {
    $language = Language::factory()->create(['code' => 'nl']);

    [, , , $creator] = prepareStandardDemoCreatorHarness($language);

    $faqWidget = $creator->createFaqWidget(new Collection([$language]));

    expect($faqWidget->assets()->count())->toBe(6)
        ->and(LayoutBuilderStandardDemoContentPage::query()->where('name', 'How was this website created?')->exists())->toBeTrue();
});

it('exercises base demo creator content collections and recursive navigation labels', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [$site, $layout, $defaultPageType, $creator] = prepareStandardDemoCreatorHarness($language);

    $parentPage = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create([
            'name' => 'Parent Page',
            'layout_id' => $layout->getKey(),
            'blueprint_id' => $defaultPageType->getKey(),
        ]);

    $childPage = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create([
            'name' => 'Child Page',
            'parent_id' => $parentPage->getKey(),
            'layout_id' => $layout->getKey(),
            'blueprint_id' => $defaultPageType->getKey(),
        ]);

    $parentPage->setRelation('children', new Illuminate\Database\Eloquent\Collection([$childPage]));

    $items = $creator->exposeNavigationPageItems(new Collection([$parentPage]), $language);
    $features = $creator->exposeCreateFeatures($site);
    $testimonials = $creator->exposeCreateTestimonials(new Collection([$language]));
    $teamMembers = $creator->exposeCreateTeamMembers(new Collection([$language]));

    $firstNavigationItem = array_values($items)[0];

    expect($firstNavigationItem['label'])->toBe($parentPage->translation?->title)
        ->and($firstNavigationItem['children'])->toHaveCount(1)
        ->and($features)->toHaveCount(7)
        ->and($testimonials)->toHaveCount(3)
        ->and($teamMembers)->toHaveCount(16);
});

it('skips duplicate page card and content block assets for existing scoped records', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [$site, $layout, $defaultPageType, $creator] = prepareStandardDemoCreatorHarness($language);

    $page = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()]);

    $relatedPage = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey(), 'blueprint_id' => $defaultPageType->getKey()]);

    Media::factory()->model($relatedPage)->collection(MediaCollectionEnum::Image)->create();

    $pageCardsWidget = resolve(WidgetCreator::class)->pagesCardWidget();
    $pageCardsWidget->assets()->create([
        'asset_id' => $relatedPage->getKey(),
        'asset_type' => $relatedPage->getMorphClass(),
        'pageable_id' => $page->getKey(),
        'pageable_type' => $page->getMorphClass(),
        'container' => 'main',
        'occurrence' => 1,
    ]);

    $creator->createPageCardsWidget($page);

    $contentWidget = resolve(WidgetCreator::class)->assetsWidget();
    $creator->createContentsWidget($contentWidget, $page, 'secondary');
    $creator->createContentsWidget($contentWidget, $page, 'secondary');

    expect($pageCardsWidget->assets()->count())->toBe(1)
        ->and($contentWidget->assets()->where([
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => 'secondary',
            'occurrence' => 1,
        ])->count())->toBe(4);
});

it('creates standard demo collection widgets with translations and reusable assets', function (): void {
    $language = Language::factory()->create(['code' => 'en']);

    [, , , $creator] = prepareStandardDemoCreatorHarness($language);

    $languages = new Collection([$language]);

    $clientLogosWidget = $creator->createClientLogosWidget($languages);
    $businessFeaturesWidget = $creator->createBusinessFeaturesWidget(Site::getDefault());
    $bannersWidget = $creator->createBannersWidget();
    $testimonialsWidget = $creator->createTestimonialsWidget($languages);
    $statisticsWidget = $creator->createStatisticsWidget();
    $teamPortfolioWidget = $creator->createTeamPortfolioWidget($languages);

    expect($clientLogosWidget->key)->toBe('client-logos')
        ->and($clientLogosWidget->assets()->count())->toBe(12)
        ->and($clientLogosWidget->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($businessFeaturesWidget->key)->toBe('business-features')
        ->and($businessFeaturesWidget->assets()->count())->toBe(7)
        ->and($bannersWidget->assets()->count())->toBe(7)
        ->and($testimonialsWidget->assets()->count())->toBe(3)
        ->and($statisticsWidget->assets()->count())->toBe(4)
        ->and($teamPortfolioWidget->assets()->count())->toBe(16);
});
