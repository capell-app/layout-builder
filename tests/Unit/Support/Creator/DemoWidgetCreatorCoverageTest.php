<?php

declare(strict_types=1);

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderDemoContentPage;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderDemoWidgetCreatorHarness;

it('creates modern and application preview demo widgets with asset-backed content', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();

    resolve(TypeCreator::class)->createWidgetTypes();
    $defaultPageType = resolve(BlueprintCreator::class)->defaultPageType();
    $layout = Layout::factory()->default()->create();

    LayoutBuilderDemoContentPage::$defaultSiteId = $site->getKey();
    LayoutBuilderDemoContentPage::$defaultLayoutId = $layout->getKey();
    LayoutBuilderDemoContentPage::$defaultBlueprintId = $defaultPageType->getKey();

    $creator = new LayoutBuilderDemoWidgetCreatorHarness;

    $widgets = [
        $creator->createModernFeatureListWidget(),
        $creator->createModernTeamMembersWidget(),
        $creator->createModernPricingTableWidget(),
        $creator->createModernTestimonialsWidget(),
        $creator->createModernFaqWidget(),
        $creator->createModernStatsSectionWidget(),
        $creator->createModernAlternatingContentWidget(),
        $creator->createModernProcessStepsWidget(),
        $creator->createModernImageGalleryWidget(),
        $creator->createApHeroBannerWidget(),
        $creator->createApCardGridWidget(),
        $creator->createApFeatureListWidget(),
        $creator->createFeatureListWidget(),
        $creator->createApCtaSectionWidget(),
        $creator->createApImageGalleryWidget(),
    ];

    $apCtaSectionWidget = capell_test_instance(Widget::query()->firstWhere('key', 'ap-cta-section'), Widget::class);
    $apCtaSectionWidgetMeta = capell_test_array($apCtaSectionWidget->meta);
    $apImageGalleryWidget = capell_test_instance(Widget::query()->firstWhere('key', 'ap-image-gallery'), Widget::class);
    $apImageGalleryWidgetMeta = capell_test_array($apImageGalleryWidget->meta);

    expect($widgets)
        ->each->toBeInstanceOf(Widget::class)
        ->and(Widget::query()->firstWhere('key', 'modern-feature-list')?->assets()->count())->toBe(6)
        ->and(Widget::query()->firstWhere('key', 'modern-team-members')?->assets()->count())->toBe(3)
        ->and(Widget::query()->firstWhere('key', 'modern-pricing-table')?->assets()->count())->toBe(3)
        ->and(Widget::query()->firstWhere('key', 'modern-faq')?->assets()->count())->toBe(5)
        ->and(Widget::query()->firstWhere('key', 'ap-card-grid')?->assets()->count())->toBe(3)
        ->and(Widget::query()->firstWhere('key', 'ap-feature-list')?->assets()->count())->toBe(4)
        ->and($apCtaSectionWidgetMeta['primary_button_text'] ?? null)->toBe('Get Started Free')
        ->and($apImageGalleryWidgetMeta['lightbox'] ?? null)->toBeTrue()
        ->and($apImageGalleryWidget->media()->count())->toBe(6);
});

it('creates page card assets for related pages in the requested occurrence', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();

    resolve(TypeCreator::class)->createWidgetTypes();
    resolve(TypeCreator::class)->createDefaultContentType();
    resolve(BlueprintCreator::class)->defaultPageType();

    $homePage = Page::factory()
        ->home()
        ->site($site)
        ->withTranslations($language)
        ->create();

    $relatedPages = Page::factory()
        ->count(3)
        ->site($site)
        ->withTranslations($language)
        ->create();

    $relatedPages->each(function (Page $page): void {
        Media::factory()
            ->model($page)
            ->collection(MediaCollectionEnum::Image)
            ->create();
    });

    $creator = new LayoutBuilderDemoWidgetCreatorHarness;

    $widget = $creator->createPageCardsWidget($homePage, container: 'related', occurrence: 2);

    expect($widget->assets()->where([
        'pageable_id' => $homePage->getKey(),
        'pageable_type' => $homePage->getMorphClass(),
        'container' => 'related',
        'occurrence' => 2,
    ])->count())->toBe(3);
});
