<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature\Widgets;

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\CreateHeroWidgetAction;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Facades\Storage;
use Pest\Expectation;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates hero widget with expected meta', function (): void {
    $widget = CreateHeroWidgetAction::run();
    WidgetAsset::factory()->count(3)->widget($widget)->create();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('hero')
        ->meta->scoped(
            fn (Expectation $expectation) => $expectation->component->toBe(WidgetComponentEnum::Hero->value),
        )
        ->assets->toHaveCount(3);
});

it('renders hero widget with page hero content', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = CreateHeroWidgetAction::run();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $heroContent = collect(fake()->paragraphs(2));
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->withTranslations(
            data: [
                'meta' => [
                    'hero' => $heroContent->map(fn (string $paragraph): string => sprintf('<p>%s</p>', $paragraph))
                        ->implode("\n"),
                ],
            ],
        )
        ->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-hero',
            fn (AssertElement $element): BaseAssert => $element->contains('.hero-item', 1)
                ->find(
                    '.hero-content',
                    fn (AssertElement $content): BaseAssert => $content->each(
                        'p',
                        fn (AssertElement $element, int $index): BaseAssert => $element->containsText($heroContent[$index]),
                    ),
                ),
        );
});

it('renders hero widget with assets', function (callable $factory, string $mediaRelation, callable $srcResolver): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $widget = CreateHeroWidgetAction::run();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $factory($widget)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widgetAssets = $widget->widgetAssets()
        ->ordered()
        ->alphabetical($language)
        ->with([
            'asset' => fn (BuilderContract $query): BuilderContract => $query->with([
                'type',
                'translation',
                'related.translation',
                'related.type',
            ])
                ->morphWith([
                    Page::class => ['pageUrl.siteDomain'],
                ]),
            $mediaRelation,
        ])
        ->get();

    // Ensure all referenced media files exist on the fake disk
    $exampleImagePath = __DIR__ . '/../../Fixtures/Files/Images/img.png';
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
            '.widget-hero',
            fn (AssertElement $element): BaseAssert => $element->contains('.hero-item', 3)
                ->contains('.hero-heading', 3)
                ->contains('h1.hero-heading', 1)
                ->contains('h2.hero-heading', 2)
                ->each(
                    '.hero-item',
                    function (AssertElement $content, int $index) use ($widgetAssets, $srcResolver): BaseAssert {
                        $url = $widgetAssets[$index]->asset->pageUrl->full_url;

                        return $content->find(
                            '.hero-content',
                            fn (AssertElement $contentElm): BaseAssert => $contentElm->containsText(
                                $widgetAssets[$index]->asset->translation->title,
                            )
                                ->find(
                                    'a',
                                    fn (AssertElement $imgElm): BaseAssert => $imgElm->has('href', $url),
                                ),
                        )
                            ->find(
                                'img.hero-slide-img',
                                fn (AssertElement $imgElm): BaseAssert => $imgElm->has('alt', $widgetAssets[$index]->asset->translation->title)
                                    ->has('src', $srcResolver($widgetAssets[$index], MediaCollectionEnum::Image)),
                            )
                            ->find(
                                'img.hero-bg-img',
                                fn (AssertElement $imgElm): BaseAssert => $imgElm->has('alt', $widgetAssets[$index]->asset->translation->title)
                                    ->has('src', $srcResolver($widgetAssets[$index], MediaCollectionEnum::BackgroundImage)),
                            )
                            ->find(
                                '.hero-related',
                                fn (AssertElement $relatedElm): BaseAssert => $relatedElm->contains('.hero-related-item', 3)
                                    ->each(
                                        '.hero-related-item',
                                        fn (AssertElement $itemElm, int $relatedIndex): BaseAssert => $itemElm->containsText(
                                            $widgetAssets[$index]->asset->related[$relatedIndex]->translation->title,
                                        ),
                                    ),
                            )
                            ->find(
                                '.hero-actions',
                                fn (AssertElement $relatedElm): BaseAssert => $relatedElm->contains('.hero-action-item', 3)
                                    ->each(
                                        '.hero-action-item',
                                        fn (AssertElement $itemElm, int $relatedIndex): BaseAssert => $itemElm->has(
                                            'href',
                                            $widgetAssets[$index]->asset->meta['actions'][$relatedIndex]['url'],
                                        ),
                                    ),
                            );
                    },
                ),
        );
})->with(
    [
        'widgetAssetHasMedia' => [
            fn (Widget $widget) => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->assetHavingRelated(3)
                ->assetHavingActions(3)
                ->has(Media::factory()->image(), 'media')
                ->has(Media::factory()->backgroundImage(), 'media'),
            'media',
            fn (WidgetAsset $widgetAsset, MediaCollectionEnum $collectionEnum) => $widgetAsset->media->firstWhere('collection_name', $collectionEnum->value)->getFullUrl(),
        ],
        'assetHavingMedia' => [
            fn (Widget $widget) => WidgetAsset::factory()->count(3)
                ->widget($widget)
                ->assetHavingRelated(3)
                ->assetHavingActions(3)
                ->assetHavingMedia()
                ->assetHavingMedia(collection: MediaCollectionEnum::BackgroundImage),
            'asset.media',
            fn (WidgetAsset $widgetAsset, MediaCollectionEnum $collectionEnum) => $widgetAsset->asset->media->firstWhere('collection_name', $collectionEnum->value)->getFullUrl(),
        ],
    ],
);

it('empty hero widget hidden', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = CreateHeroWidgetAction::run();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertDoesntExist('.widget-hero');
});
