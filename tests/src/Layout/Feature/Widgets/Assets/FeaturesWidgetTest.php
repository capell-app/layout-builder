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
            fn (Expectation $meta) => $meta->view_file->toBe('capell-layout::components.widget.assets.features'),
        )
        ->assets->toHaveCount(3);
});

it('renders asset features widget on page', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->featuresWidget();
    $factory($widget)->create();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
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
            fn (Widget $widget) => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->has(Media::factory()->image(), 'media'),
            'media',
            fn ($widgetAsset) => $widgetAsset->media->first()->getFullUrl(),
        ],
        'assetHavingMedia' => [
            fn (Widget $widget) => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->assetHavingMedia(),
            'asset.media',
            fn ($widgetAsset) => $widgetAsset->asset->media->first()->getFullUrl(),
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
