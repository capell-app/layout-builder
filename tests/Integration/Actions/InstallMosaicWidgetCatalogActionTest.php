<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Mosaic\Actions\InstallMosaicWidgetCatalogAction;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Models\Widget;
use Capell\Navigation\Models\Navigation;

it('installs the mosaic widget catalog with translations and expected metadata', function (): void {
    $english = Language::factory()->english()->create();
    $french = Language::factory()->create(['code' => 'fr', 'name' => 'French']);

    InstallMosaicWidgetCatalogAction::run(collect([$english, $french]), extraWidgets: true);

    $widgetKeys = Widget::query()->pluck('key')->all();

    expect($widgetKeys)
        ->toContain(
            'breadcrumbs',
            'children',
            'assets',
            'gallery',
            'latest-pages',
            'media-carousel',
            'page-content',
            'page-slot',
            'pages-card',
            'siblings',
            'default',
            'assets-accordion',
            'assets-banner',
            'assets-block',
            'asset-features',
            'asset-testimonials',
            'widget-navigation',
            'widget-navigation-tabs',
            'banner-image',
        );

    $childrenWidget = Widget::query()->where('key', 'children')->firstOrFail();
    $galleryWidget = Widget::query()->where('key', 'gallery')->firstOrFail();
    $latestPagesWidget = Widget::query()->where('key', 'latest-pages')->firstOrFail();
    $mediaCarouselWidget = Widget::query()->where('key', 'media-carousel')->firstOrFail();
    $navigationTabsWidget = Widget::query()->where('key', 'widget-navigation-tabs')->firstOrFail();

    expect($childrenWidget->translations()->count())->toBe(2)
        ->and($galleryWidget->translations()->count())->toBe(2)
        ->and($latestPagesWidget->translations()->count())->toBe(2)
        ->and($childrenWidget->meta['component'])->toBe(WidgetComponentEnum::PageChildren->value)
        ->and($mediaCarouselWidget->meta['component'])->toBe(WidgetComponentEnum::AssetCarousel->value)
        ->and($navigationTabsWidget->meta['view_file'])->toBe('capell-mosaic::components.widget.navigation.tabs');

    expect(Navigation::query()->whereIn('key', ['navigation', 'navigation-tabs'])->count())->toBe(2);
});

it('can be run repeatedly without duplicating widgets, translations, or catalog navigations', function (): void {
    $language = Language::factory()->english()->create();
    $languages = collect([$language]);

    InstallMosaicWidgetCatalogAction::run($languages, extraWidgets: true);
    InstallMosaicWidgetCatalogAction::run($languages, extraWidgets: true);

    expect(Widget::query()->count())->toBe(19)
        ->and(Widget::query()->where('key', 'children')->firstOrFail()->translations()->count())->toBe(1)
        ->and(Navigation::query()->whereIn('key', ['navigation', 'navigation-tabs'])->count())->toBe(2);
});

it('preserves customized widget meta on rerun while backfilling missing catalog keys', function (): void {
    $language = Language::factory()->english()->create();
    $languages = collect([$language]);

    InstallMosaicWidgetCatalogAction::run($languages, extraWidgets: true);

    $galleryWidget = Widget::query()->where('key', 'gallery')->firstOrFail();
    $galleryWidget->forceFill([
        'meta' => [
            ...$galleryWidget->meta,
            'margin' => ['custom-admin-margin'],
        ],
    ])->save();

    $navigationTabsWidget = Widget::query()->where('key', 'widget-navigation-tabs')->firstOrFail();
    $navigationTabsWidget->forceFill([
        'meta' => collect($navigationTabsWidget->meta)
            ->except(['view_file'])
            ->all(),
    ])->save();

    InstallMosaicWidgetCatalogAction::run($languages, extraWidgets: true);

    expect(Widget::query()->where('key', 'gallery')->firstOrFail()->meta['margin'])
        ->toBe(['custom-admin-margin'])
        ->and(Widget::query()->where('key', 'widget-navigation-tabs')->firstOrFail()->meta['view_file'])
        ->toBe('capell-mosaic::components.widget.navigation.tabs');
});
