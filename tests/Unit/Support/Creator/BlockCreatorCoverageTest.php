<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\BlockCreator;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;

it('creates the legacy support blocks with expected metadata and translations', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $languages = collect([$language]);

    resolve(TypeCreator::class)->createBlockTypes();

    $creator = new BlockCreator;

    $blocks = [
        $creator->breadcrumbBlock(),
        $creator->childrenBlock(languages: $languages),
        $creator->assetsBlock(),
        $creator->galleryBlock(languages: $languages),
        $creator->latestPagesBlock(languages: $languages),
        $creator->mediaCarouselBlock(),
        $creator->pageContentBlock(),
        $creator->pagesCardBlock(),
        $creator->pageSlotBlock(),
        $creator->siblingsBlock(languages: $languages),
        $creator->defaultBlock(),
        $creator->accordionBlock(),
        $creator->bannerBlock(),
        $creator->blockBlock(),
        $creator->featuresBlock(),
        $creator->testimonialsBlock(),
        $creator->bannerImageBlock(),
        $creator->apHeroBannerBlock(),
        $creator->apCardGridBlock(),
        $creator->apFeatureListBlock(),
        $creator->apCtaSectionBlock(),
        $creator->apImageGalleryBlock(),
    ];

    expect($blocks)
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
            'assets-block',
            'asset-features',
            'asset-testimonials',
            'banner-image',
            'ap-hero-banner',
            'ap-card-grid',
            'ap-feature-list',
            'ap-cta-section',
            'ap-image-gallery',
        ])->count())->toBe(22);

    $breadcrumbsBlock = capell_test_instance(Widget::query()->firstWhere('key', 'breadcrumbs'), Widget::class);
    $pageContentBlock = capell_test_instance(Widget::query()->firstWhere('key', 'page-content'), Widget::class);
    $pageContentBlockMeta = capell_test_array($pageContentBlock->meta);
    $mediaCarouselBlock = capell_test_instance(Widget::query()->firstWhere('key', 'media-carousel'), Widget::class);
    $mediaCarouselBlockMeta = capell_test_array($mediaCarouselBlock->meta);
    $apCardGridBlock = capell_test_instance(Widget::query()->firstWhere('key', 'ap-card-grid'), Widget::class);
    $apCardGridBlockMeta = capell_test_array($apCardGridBlock->meta);
    $childrenBlock = capell_test_instance(Widget::query()->firstWhere('key', 'children'), Widget::class);
    $siblingsBlock = capell_test_instance(Widget::query()->firstWhere('key', 'siblings'), Widget::class);

    expect($breadcrumbsBlock->component)->toBe(BlockComponentEnum::PageBreadcrumbs->value)
        ->and($pageContentBlockMeta['page_content'] ?? null)->toBe(['title', 'content'])
        ->and($mediaCarouselBlockMeta['carousel_auto_play'] ?? null)->toBeTrue()
        ->and($apCardGridBlockMeta['columns'] ?? null)->toBe(3)
        ->and($childrenBlock->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($siblingsBlock->translations()->where('language_id', $language->getKey())->exists())->toBeTrue();
});
