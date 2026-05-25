<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class LayoutBuilderAssetHarness extends LayoutBuilder
{
    #[Override]
    public function assertCanUpdateLayout(): void {}

    #[Override]
    public function assertCanEditContent(): void {}

    /**
     * @param  array<string, array<int, Widget>>  $containerBlocks
     */
    public function setContainerBlocks(array $containerBlocks): void
    {
        $this->containerBlocks = $containerBlocks;
    }

    public function exposeUpdatePageAssets(string $containerKey, int $blockIndex, ?bool $hasPageAssets = null): void
    {
        $this->updatePageAssets($containerKey, $blockIndex, $hasPageAssets);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeMapBlockAssets(Widget $block, string $containerKey, ?string $oldContainerKey = null): array
    {
        return $this->mapBlockAssets($block, $containerKey, $oldContainerKey);
    }

    /**
     * @param  array<array-key, mixed>  $blockAssets
     * @param  EloquentCollection<int, WidgetAsset>|null  $allBlockAssets
     * @return EloquentCollection<int, WidgetAsset>
     */
    public function exposeSetupBlockAssets(
        string $containerKey,
        int $blockIndex,
        array $blockAssets,
        ?EloquentCollection $allBlockAssets,
        Widget $block,
    ): EloquentCollection {
        return $this->setupBlockAssets($containerKey, $blockIndex, $blockAssets, $allBlockAssets, $block);
    }

    /**
     * @param  EloquentCollection<int, WidgetAsset>  $assets
     * @return EloquentCollection<int, WidgetAsset>
     */
    public function exposeFilterContainerBlockAssets(
        EloquentCollection $assets,
        string $containerKey,
        int $blockOccurrence,
        ?Widget $block = null,
    ): EloquentCollection {
        return $this->filterContainerBlockAssets($assets, $containerKey, $blockOccurrence, $block);
    }

    public function exposeSaveOriginalAssets(): void
    {
        $this->saveOriginalAssets();
    }

    public function exposeDeleteRemovedBlockAssets(): void
    {
        $this->deleteRemovedBlockAssets();
    }
}

it('reorders selects and removes in-memory block assets predictably', function (): void {
    $harness = new LayoutBuilderAssetHarness;
    $harness->layout = Layout::factory()->create();
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = [
        'main' => [
            [
                ['asset_type' => 'page', 'asset_id' => 10, 'order' => 1],
                ['asset_type' => 'page', 'asset_id' => 20, 'order' => 2],
                ['asset_type' => 'section', 'asset_id' => 'external-id', 'order' => 3],
            ],
        ],
    ];
    $harness->selectedRecords = ['main' => [[]]];

    expect($harness->canMoveAssetUp('main', 0, 0))->toBeFalse()
        ->and($harness->canMoveAssetDown('main', 0, 0))->toBeTrue()
        ->and($harness->countBlockAssets('main', 0))->toBe(3)
        ->and($harness->getBlockAssetsByType('main', 0, 'page'))->toBe([10, 20]);

    $harness->moveAssetDown('main', 0, 0);

    expect($harness->getBlockAsset('main', 0, 0)['asset_id'])->toBe(20)
        ->and($harness->getBlockAsset('main', 0, 1)['asset_id'])->toBe(10)
        ->and($harness->getBlockAsset('main', 0, 0)['order'])->toBe(1)
        ->and($harness->layoutModified)->toBeTrue();

    $harness->selectAllAssets('main', 0);

    expect($harness->getSelectedAssets('main', 0))->toBe(['page.20', 'page.10', 'section.external-id']);

    $harness->selectedRecords['main'][0] = ['page.20', 'section.external-id'];
    $harness->removeSelectedAssets('main', 0);

    expect($harness->getBlockAssets('main', 0))
        ->toHaveCount(1)
        ->and($harness->getBlockAsset('main', 0, 0)['asset_id'])->toBe(10)
        ->and($harness->getSelectedAssets('main', 0))->toBe([]);

    $harness->deSelectAllAssets('main', 0);

    expect($harness->getSelectedAssets('main', 0))->toBe([]);
});

it('maps filters and restores persisted block assets for page scoped state', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $otherPage = Page::factory()->withTranslations()->create();
    $layout = Layout::factory()->create();
    $block = Widget::factory()->create(['key' => 'hero']);
    $globalAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($page)
        ->create(['container' => null, 'occurrence' => 1, 'order' => 2, 'workspace_id' => 0]);
    $pageAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($page)
        ->create([
            'container' => 'main',
            'occurrence' => 1,
            'order' => 1,
            'workspace_id' => 0,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
        ]);
    $otherPageAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($otherPage)
        ->create([
            'container' => 'main',
            'occurrence' => 1,
            'order' => 3,
            'workspace_id' => 0,
            'pageable_id' => $otherPage->getKey(),
            'pageable_type' => $otherPage->getMorphClass(),
        ]);

    $block->setRelation('assets', new EloquentCollection([$globalAsset, $pageAsset, $otherPageAsset]));

    $harness = new LayoutBuilderAssetHarness;
    $harness->layout = $layout;
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $block->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = ['main' => [[]]];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->setContainerBlocks(['main' => [$block]]);

    $allBlockAssets = WidgetAsset::query()
        ->whereKey([$globalAsset->getKey(), $pageAsset->getKey(), $otherPageAsset->getKey()])
        ->get();

    $mappedAssets = $harness->exposeMapBlockAssets($block, 'main', 'old-main');

    expect($mappedAssets[1])
        ->toMatchArray([
            'id' => $pageAsset->getKey(),
            'asset_id' => $page->getKey(),
            'pageable_id' => $page->getKey(),
            'container' => 'main',
            'old_container' => 'old-main',
        ]);

    $filteredAssets = $harness->exposeFilterContainerBlockAssets(
        $allBlockAssets,
        'main',
        1,
        $block,
    );

    expect($filteredAssets->pluck('id')->all())->toBe([$globalAsset->getKey()]);

    $restoredAssets = $harness->exposeSetupBlockAssets(
        'main',
        0,
        [
            [
                'id' => $pageAsset->getKey(),
                'asset_type' => $pageAsset->asset_type,
                'asset_id' => $pageAsset->asset_id,
                'old_container' => 'main',
                'order' => 9,
            ],
            [
                'asset_type' => $otherPageAsset->asset_type,
                'asset_id' => $otherPageAsset->asset_id,
                'old_container' => 'main',
            ],
        ],
        $allBlockAssets,
        $block,
    );

    expect($restoredAssets)->toHaveCount(1)
        ->and($restoredAssets->first()->getKey())->toBe($pageAsset->getKey())
        ->and($restoredAssets->first()->order)->toBe(9);

    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $pageAsset->getKey(),
                    'asset_type' => $pageAsset->asset_type,
                    'asset_id' => $pageAsset->asset_id,
                    'order' => 1,
                    'occurrence' => 1,
                    'workspace_id' => 0,
                    'pageable_id' => null,
                    'pageable_type' => null,
                ],
            ],
        ],
    ];

    expect($harness->hasPageAssets('main', 0))->toBeFalse()
        ->and($harness->shouldAddPageAssets('main', 0))->toBeFalse();

    $harness->exposeUpdatePageAssets('main', 0, true);

    expect($harness->hasPageAssets('main', 0))->toBeTrue()
        ->and($harness->assets['main'][0][0]['pageable_id'])->toBe($page->getKey())
        ->and($harness->shouldAddPageAssets('main', 0))->toBeTrue();

    $harness->exposeUpdatePageAssets('main', 0, false);

    expect($harness->hasPageAssets('main', 0))->toBeFalse()
        ->and($harness->assets['main'][0][0]['pageable_id'])->toBeNull();
});

it('captures original assets and deletes only removed persisted asset records', function (): void {
    $page = Page::factory()->withTranslations()->create();
    $removedPage = Page::factory()->withTranslations()->create();
    $layout = Layout::factory()->create();
    $block = Widget::factory()->create(['key' => 'hero']);
    $keptAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($page)
        ->create(['container' => null, 'occurrence' => 1, 'order' => 1, 'workspace_id' => 0]);
    $removedAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($removedPage)
        ->create(['container' => null, 'occurrence' => 1, 'order' => 2, 'workspace_id' => 0]);

    $block->setRelation('assets', new EloquentCollection([$keptAsset, $removedAsset]));

    $harness = new LayoutBuilderAssetHarness;
    $harness->layout = $layout;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $block->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $keptAsset->getKey(),
                    'asset_type' => $keptAsset->asset_type,
                    'asset_id' => $keptAsset->asset_id,
                    'order' => 1,
                    'occurrence' => 1,
                    'workspace_id' => 0,
                ],
                [
                    'id' => $removedAsset->getKey(),
                    'asset_type' => $removedAsset->asset_type,
                    'asset_id' => $removedAsset->asset_id,
                    'order' => 2,
                    'occurrence' => 1,
                    'workspace_id' => 0,
                ],
            ],
        ],
    ];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->setContainerBlocks(['main' => [$block]]);

    $harness->exposeSaveOriginalAssets();

    $harness->assets['main'][0] = [
        [
            'id' => $keptAsset->getKey(),
            'asset_type' => $keptAsset->asset_type,
            'asset_id' => $keptAsset->asset_id,
            'order' => 1,
            'occurrence' => 1,
            'workspace_id' => 0,
        ],
    ];

    $harness->exposeDeleteRemovedBlockAssets();

    expect(WidgetAsset::query()->whereKey($keptAsset->getKey())->exists())->toBeTrue()
        ->and(WidgetAsset::query()->whereKey($removedAsset->getKey())->exists())->toBeFalse();
});
