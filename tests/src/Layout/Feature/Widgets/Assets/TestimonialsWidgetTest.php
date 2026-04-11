<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Database\Factories\WidgetAssetFactory;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
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
            fn (Expectation $meta) => $meta
                ->view_file->toBe('capell-layout::components.widget.asset.testimonials')
                ->carousel_effect->toBe('fade')
                ->carousel_drag->toBeFalse()
                ->carousel_touch->toBeFalse(),
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
            fn (AssertElement $widgetElement): BaseAssert => $widgetElement
                ->find(
                    '.swiper',
                    fn (AssertElement $carouselElement): BaseAssert => $carouselElement
                        ->has('data-carousel', '1')
                        ->has('data-carousel-effect', 'fade')
                        ->has('data-carousel-autoplay', '1')
                        ->has('data-carousel-loop', '1')
                        ->has('data-carousel-pagination', '1')
                        ->has('data-carousel-drag', '0')
                        ->has('data-carousel-touch', '0'),
                )
                ->contains('.swiper-controls[data-carousel-controls]', count: 1)
                ->contains('.widget-testimonial-item', count: 3)
                ->each(
                    '.widget-testimonial-item',
                    fn (AssertElement $itemElement, int $index): BaseAssert => $itemElement->find(
                        'img',
                        fn (AssertElement $imageElement): BaseAssert => $imageElement->has('alt', $widgetAssets[$index]->asset->translation->title)
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
            function (WidgetAsset $widgetAsset): string {
                $media = $widgetAsset->asset->media->first();

                throw_unless($media instanceof Media, RuntimeException::class, 'Expected asset media to be available.');

                return $media->getFullUrl();
            },
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
