<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

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

it('renders gallery widget on page with assets', function (callable $factory, array $with, callable $srcResolver): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->galleryWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $factory($widget)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();
    $widgetAssets = $widget->widgetAssets()
        ->ordered()
        ->with($with)
        ->get();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-media-gallery',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-media-item', count: 3)
                ->each(
                    '.widget-media-item',
                    function (AssertElement $itemElm, int $index) use ($widgetAssets, $srcResolver): void {
                        $itemElm->find(
                            'img',
                            function (AssertElement $imgElm) use ($widgetAssets, $index, $srcResolver): void {
                                $imgElm->has(
                                    'alt',
                                    $widgetAssets[$index]->asset->translation->title,
                                )->has('src', $srcResolver($widgetAssets[$index]));
                            },
                        );
                    },
                ),
        );
})->with(
    [
        'widgetAssetHasMedia' => [
            fn ($widget) => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->has(Media::factory()->image(), 'media'),
            [
                'asset.type',
                'asset.translation',
                'media',
            ],
            fn ($widgetAsset) => $widgetAsset->media->first()->getFullUrl(),
        ],
        'assetHavingMedia' => [
            fn ($widget) => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->assetHavingMedia(),
            [
                'asset.type',
                'asset.translation',
                'asset.media',
            ],
            fn ($widgetAsset) => $widgetAsset->asset->media->first()->getFullUrl(),
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
        ->assertElementExists('main', fn (AssertElement $assert) => $assert->doesntContain('.widget-media-gallery'));
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
