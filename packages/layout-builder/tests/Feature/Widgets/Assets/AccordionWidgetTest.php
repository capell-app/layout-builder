<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Database\Factories\WidgetAssetFactory;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Support\Facades\Storage;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates asset accordion widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->accordionWidget();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('assets-accordion')
        ->meta->scoped(
            fn (Expectation $meta) => $meta->component->toBe(WidgetComponentEnum::AssetAccordion->value),
        )
        ->assets->toHaveCount(3);
});

it('renders asset accordion widget on page', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->accordionWidget();
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

    // Ensure all referenced media files exist on the fake disk
    $exampleImagePath = __DIR__ . '/../../../Fixtures/Files/Images/img.png';
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
            '.widget-assets-accordion',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-accordion-item', count: 3)
                ->each(
                    '.widget-accordion-item',
                    fn (AssertElement $asset, int $index): BaseAssert => $asset->containsText($widgetAssets[$index]->asset->translation->title)
                        ->containsText(strip_tags((string) $widgetAssets[$index]->asset->translation->content))
                        ->find(
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

it('empty asset accordion widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->accordionWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-assets-accordion');
});

it('empty asset accordion widget visible', function (): void {
    config()->set('capell-layout-builder.widget.skip_render_empty', false);

    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->accordionWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-assets-accordion');
});
