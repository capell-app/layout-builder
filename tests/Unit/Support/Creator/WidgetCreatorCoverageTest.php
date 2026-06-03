<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;

it('creates the legacy support widgets with expected metadata and translations', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $languages = collect([$language]);

    resolve(TypeCreator::class)->createWidgetTypes();

    $creator = new WidgetCreator;

    $widgets = [
        $creator->breadcrumbWidget(),
        $creator->childrenWidget(languages: $languages),
        $creator->assetsWidget(),
        $creator->galleryWidget(languages: $languages),
        $creator->latestPagesWidget(languages: $languages),
        $creator->mediaCarouselWidget(),
        $creator->pageContentWidget(),
        $creator->pagesCardWidget(),
        $creator->pageSlotWidget(),
        $creator->siblingsWidget(languages: $languages),
        $creator->defaultWidget(),
        $creator->accordionWidget(),
        $creator->bannerWidget(),
        $creator->widgetWidget(),
        $creator->featuresWidget(),
        $creator->testimonialsWidget(),
        $creator->bannerImageWidget(),
        $creator->apHeroBannerWidget(),
        $creator->apCardGridWidget(),
        $creator->apFeatureListWidget(),
        $creator->apCtaSectionWidget(),
        $creator->apImageGalleryWidget(),
    ];

    expect($widgets)
        ->each->toBeInstanceOf(Widget::class)
        ->and(Widget::query()->whereIn('key', [
            'breadcrumbs',
            'children',
            'assets',
            'gallery',
            'latest-pages',
            'media-carousel',
            'page-content',
            'pages-card',
            'page-slot',
            'siblings',
            'default',
            'assets-accordion',
            'assets-banner',
            'assets-widget',
            'asset-features',
            'asset-testimonials',
            'banner-image',
            'ap-hero-banner',
            'ap-card-grid',
            'ap-feature-list',
            'ap-cta-section',
            'ap-image-gallery',
        ])->count())->toBe(22);

    $breadcrumbsWidget = capell_test_instance(Widget::query()->firstWhere('key', 'breadcrumbs'), Widget::class);
    $pageContentWidget = capell_test_instance(Widget::query()->firstWhere('key', 'page-content'), Widget::class);
    $pageContentWidgetMeta = capell_test_array($pageContentWidget->meta);
    $mediaCarouselWidget = capell_test_instance(Widget::query()->firstWhere('key', 'media-carousel'), Widget::class);
    $mediaCarouselWidgetMeta = capell_test_array($mediaCarouselWidget->meta);
    $apCardGridWidget = capell_test_instance(Widget::query()->firstWhere('key', 'ap-card-grid'), Widget::class);
    $apCardGridWidgetMeta = capell_test_array($apCardGridWidget->meta);
    $childrenWidget = capell_test_instance(Widget::query()->firstWhere('key', 'children'), Widget::class);
    $siblingsWidget = capell_test_instance(Widget::query()->firstWhere('key', 'siblings'), Widget::class);

    expect($breadcrumbsWidget->component)->toBe(WidgetComponentEnum::PageBreadcrumbs->value)
        ->and($pageContentWidgetMeta['page_content'] ?? null)->toBe(['title', 'content'])
        ->and($mediaCarouselWidgetMeta['carousel_auto_play'] ?? null)->toBeTrue()
        ->and($apCardGridWidgetMeta['columns'] ?? null)->toBe(3)
        ->and($childrenWidget->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($siblingsWidget->translations()->where('language_id', $language->getKey())->exists())->toBeTrue();
});
