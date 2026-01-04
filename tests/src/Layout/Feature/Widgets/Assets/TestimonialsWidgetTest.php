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

it('creates asset testimonials widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->testimonialsWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('asset-testimonials')
        ->meta->scoped(
            fn (Expectation $meta) => $meta->view_file->toBe('capell-layout::components.widget.assets.testimonials'),
        )
        ->assets->toHaveCount(3);
});

it('renders asset testimonials widget on page', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->testimonialsWidget();
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
            '.widget-assets-testimonials',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-testimonial-item', count: 3)
                ->each(
                    '.widget-testimonial-item',
                    fn (AssertElement $itemElm, int $index): BaseAssert => $itemElm->find(
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

it('empty asset testimonials widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->testimonialsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-assets-testimonials');
});

it('empty asset testimonials widget visible', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->testimonialsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-assets-testimonials');
});
