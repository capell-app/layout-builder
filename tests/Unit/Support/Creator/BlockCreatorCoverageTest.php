<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Models\Block;
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
        ->each->toBeInstanceOf(Block::class)
        ->and(Block::query()->whereIn('key', [
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

    expect(Block::query()->firstWhere('key', 'breadcrumbs')?->component)->toBe(BlockComponentEnum::PageBreadcrumbs->value)
        ->and(Block::query()->firstWhere('key', 'page-content')?->meta['page_content'])->toBe(['title', 'content'])
        ->and(Block::query()->firstWhere('key', 'media-carousel')?->meta['carousel_auto_play'])->toBeTrue()
        ->and(Block::query()->firstWhere('key', 'ap-card-grid')?->meta['columns'])->toBe(3)
        ->and(Block::query()->firstWhere('key', 'children')?->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and(Block::query()->firstWhere('key', 'siblings')?->translations()->where('language_id', $language->getKey())->exists())->toBeTrue();
});
