<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Database\Factories\WidgetAssetFactory;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Support\Facades\Storage;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates media carousel widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->mediaCarouselWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('media-carousel')
        ->meta->scoped(
            fn (Expectation $meta) => $meta->component->toBe(WidgetComponentEnum::AssetCarousel->value),
        )
        ->assets->toHaveCount(3);
});

it('renders carousel widget on page with assets', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    Storage::fake('public');
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->mediaCarouselWidget();
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

    // Ensure all referenced media files exist on the fake disk
    $exampleImagePath = __DIR__ . '/../../../../Fixtures/Files/Images/img.png';
    $exampleImage = file_get_contents($exampleImagePath);
    foreach ($widgetAssets as $widgetAsset) {
        $mediaCollection = data_get($widgetAsset, $mediaRelation);
        foreach ($mediaCollection as $media) {
            Storage::disk('public')->put($media->getPathRelativeToRoot(), $exampleImage);
        }
    }

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-media-carousel',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-media-item', count: 3)
                ->each(
                    '.widget-media-item',
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

it('empty media carousel widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->mediaCarouselWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-media-carousel');
});

it('empty media carousel widget visible', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->mediaCarouselWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-media-carousel');
});
