<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates gallery widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->galleryWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('gallery')
        ->assets->toHaveCount(3);
});

it('renders gallery widget on page with assets', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->galleryWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $factory($widget)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();
    $widgetAssets = $widget->widgetAssets()
        ->ordered()
        ->with([
            'asset.type',
            'asset.translation',
            $mediaRelation,
        ])
        ->get();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-media-gallery',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-media-item', count: 3)
                ->each(
                    '.widget-media-item',
                    fn (AssertElement $itemElm, int $index): BaseAssert => $itemElm->find(
                        'img',
                        fn (AssertElement $imgElm): BaseAssert => $imgElm->has(
                            'alt',
                            $widgetAssets[$index]->asset->translation->title,
                        )
                            ->has('src', $srcResolver($widgetAssets[$index])),
                    ),
                ),
        );
})->with(
    [
        'widgetAssetHasMedia' => [
            fn (Widget $widget): \Capell\Layout\Database\Factories\WidgetAssetFactory => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->has(Media::factory()->image(), 'media'),
            'media',
            fn (WidgetAsset $widgetAsset): string => $widgetAsset->media->first()->getFullUrl(),
        ],
        'assetHavingMedia' => [
            fn (Widget $widget): \Capell\Layout\Database\Factories\WidgetAssetFactory => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->assetHavingMedia(),
            'asset.media',
            fn (WidgetAsset $widgetAsset): string => $widgetAsset->asset->media->first()->getFullUrl(),
        ],
    ],
);

it('empty gallery widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->galleryWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-media-gallery');
});

it('empty gallery widget visible', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->galleryWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-media-gallery');
});
