<?php

declare(strict_types=1);

use Capell\Admin\Actions\Pages\BuildFrontendSourceMapAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;

it('builds a frontend source map for layout elements and element assets', function (): void {
    $language = Language::factory()->english()->create();
    $element = Element::factory()->create(['key' => 'hero', 'name' => 'Hero element']);
    $relatedPage = Page::factory()
        ->withTranslations($language, ['title' => 'Related Bulldog page'])
        ->create();
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'elements' => [
                    ['element_key' => 'hero', 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    $page = Page::factory()
        ->layout($layout)
        ->withTranslations($language, ['title' => 'Homepage Bulldog'])
        ->create();

    Translation::factory()
        ->translatable($element)
        ->language($language)
        ->create(['title' => 'Hero heading']);

    $elementAsset = ElementAsset::factory()
        ->widget($element)
        ->asset($relatedPage)
        ->container('main')
        ->occurrence(1)
        ->create();

    $itemsByType = BuildFrontendSourceMapAction::run($page)
        ->keyBy('typeLabel')
        ->all();

    expect($itemsByType)->toHaveKeys([
        'Element',
        'Element translation',
        'Element asset',
        'Related content',
        'Related translation',
    ])
        ->and($itemsByType['Element']->preview)->toBe('Hero element')
        ->and($itemsByType['Element']->fieldPath)->toBe('layout.containers.main.elements.0.element_key')
        ->and($itemsByType['Related content']->preview)->toBe('Related Bulldog page')
        ->and($itemsByType['Related translation']->fieldPath)->toBe('layout.containers.main.elements.0.assets.' . $elementAsset->getKey() . '.asset.translations.en.title');
});

it('omits unpublished layout elements and their assets from the frontend source map', function (): void {
    Element::factory()->create(['key' => 'visible', 'name' => 'Visible element']);
    $hiddenElement = Element::factory()->create([
        'key' => 'hidden',
        'name' => 'Hidden element',
        'visible_from' => now()->addDay(),
    ]);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'elements' => [
                    ['element_key' => 'visible'],
                    ['element_key' => 'hidden'],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->layout($layout)->withTranslations()->create();
    $hiddenAssetPage = Page::factory()
        ->withTranslations(null, ['title' => 'Hidden asset page'])
        ->create();

    ElementAsset::factory()
        ->widget($hiddenElement)
        ->asset($hiddenAssetPage)
        ->container('main')
        ->occurrence(1)
        ->create();

    $previews = BuildFrontendSourceMapAction::run($page)->pluck('preview')->all();

    expect($previews)->toContain('Visible element')
        ->and($previews)->not->toContain('Hidden element', 'Hidden asset page');
});
