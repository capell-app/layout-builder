<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Database\Factories\WidgetAssetFactory;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates asset features widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->featuresWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('asset-features')
        ->meta->scoped(
            fn (Expectation $meta) => $meta->view_file->toBe('capell-layout::components.widget.asset.features'),
        )
        ->assets->toHaveCount(3);
});

it('renders asset features widget on page', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->featuresWidget();
    $factory($widget)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widgetAssets = $widget->widgetAssets()
        ->ordered()
        ->alphabetical($language)
        ->with([
            'asset.type',
            'asset.translation',
            $mediaRelation,
        ])
        ->get();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-assets-features',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-features-item', count: 3)
                ->each(
                    '.widget-features-item',
                    fn (AssertElement $asset, int $index): BaseAssert => $asset->containsText($widgetAssets[$index]->asset->translation->title)
                        ->containsText(strip_tags((string) $widgetAssets[$index]->asset->translation->content))
                        ->find(
                            'img',
                            fn (AssertElement $imgElm): BaseAssert => $imgElm->has('alt', $widgetAssets[$index]->asset->translation->title)
                                ->has('src', $srcResolver($widgetAssets[$index])),
                        ),
                ),
        );
})->with(
    [
        'widgetAssetHasMedia' => [
            fn (Widget $widget): WidgetAssetFactory => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->has(Media::factory()->image(), 'media'),
            'media',
            fn (WidgetAsset $widgetAsset): string => $widgetAsset->media->first()->getFullUrl(),
        ],
        'assetHavingMedia' => [
            fn (Widget $widget): WidgetAssetFactory => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->assetHavingMedia(),
            'asset.media',
            fn (WidgetAsset $widgetAsset): string => $widgetAsset->asset->media->first()->getFullUrl(),
        ],
    ],
);

it('empty asset features widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->featuresWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-assets-features');
});

it('empty asset features widget visible', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->featuresWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-assets-features');
});
