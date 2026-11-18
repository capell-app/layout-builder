<?php

declare(strict_types=1);

use Capell\Core\Contracts\Capabilities\LayoutWidgetHost;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Capabilities\CapabilityRegistry;
use Capell\LayoutBuilder\Capabilities\LayoutBuilderWidgetHost;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\CapellLayoutManager;

afterEach(function (): void {
    CapellLayoutManager::clearContainerWidgets();
});

it('renders preview widgets with unsaved asset state and forgets them afterwards', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->withTranslations($language)->language($language)->create();
    $layout = Layout::factory()->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $linkedPage = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $block = Widget::factory()->create(['key' => 'asset-rail']);
    $savedAsset = WidgetAsset::factory()
        ->block($block)
        ->page($page, 'main', 1)
        ->asset($linkedPage)
        ->create([
            'meta' => ['caption' => 'Saved caption'],
            'order' => 1,
        ]);

    $containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $block->key, 'occurrence' => 1],
            ],
        ],
    ];
    $assets = [
        'main' => [
            [
                [
                    'id' => $savedAsset->getKey(),
                    'block_id' => $block->getKey(),
                    'asset_type' => AssetEnum::Page->value,
                    'asset_id' => $linkedPage->getKey(),
                    'container' => 'main',
                    'pageable_type' => $page->getMorphClass(),
                    'pageable_id' => $page->getKey(),
                    'occurrence' => 1,
                    'order' => 1,
                    'meta' => ['caption' => 'Unsaved caption'],
                ],
            ],
        ],
    ];

    $host = resolve(CapabilityRegistry::class)->get(LayoutWidgetHost::class);
    $previewBlock = $host->withPreviewWidgets($page, $language, $containers, $assets, static function (bool $registered): ?Widget {
        expect($registered)->toBeTrue();

        return CapellLayoutManager::getStoredContainerWidget('main', 'asset-rail', 1);
    });
    $previewAsset = $previewBlock instanceof Widget ? $previewBlock->assets->firstOrFail() : null;

    if (! $previewAsset instanceof WidgetAsset) {
        throw new RuntimeException('Expected the preview asset to be stored.');
    }

    expect($host)->toBeInstanceOf(LayoutBuilderWidgetHost::class)
        ->and(CapellLayoutManager::getStoredContainerWidget('main', 'asset-rail', 1))->toBeNull()
        ->and($previewAsset->meta)->toBe(['caption' => 'Unsaved caption'])
        ->and($previewAsset->asset)->toBeInstanceOf(Page::class);

    if (! $previewAsset->asset instanceof Page) {
        throw new RuntimeException('Expected the preview asset to resolve to a page.');
    }

    expect($previewAsset->asset->is($linkedPage))->toBeTrue()
        ->and($savedAsset->fresh()->meta)->toBe(['caption' => 'Saved caption']);
});

it('forgets preview widgets when the preview render throws', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->withTranslations($language)->language($language)->create();
    $layout = Layout::factory()->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $block = Widget::factory()->create(['key' => 'throwing-preview']);

    expect(fn (): mixed => resolve(LayoutWidgetHost::class)->withPreviewWidgets(
        $page,
        $language,
        ['main' => ['widgets' => [['widget_key' => $block->key, 'occurrence' => 1]]]],
        [],
        static fn (bool $registered): never => throw new RuntimeException('render failed'),
    ))->toThrow(RuntimeException::class, 'render failed')
        ->and(CapellLayoutManager::getStoredContainerWidget('main', 'throwing-preview', 1))->toBeNull();
});
