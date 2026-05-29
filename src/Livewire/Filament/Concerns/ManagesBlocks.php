<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutBlockAction;
use Capell\LayoutBuilder\Actions\ResolveAdminBlockPreviewDataAction;
use Capell\LayoutBuilder\Data\AdminBlockPreviewData;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait ManagesBlocks
{
    public function addBlockToContainer(Widget $block, string $containerKey): int
    {
        $this->assertCanUpdateLayout();

        $occurrence = $this->getLastContainerBlockOccurrence($containerKey, $block->key) + 1;

        $this->containers[$containerKey]['widgets'][] = [
            'widget_key' => $block->key,
            'occurrence' => $occurrence,
        ];

        $index = array_key_last($this->containers[$containerKey]['widgets']);

        $this->containerBlocks[$containerKey][$index] = $block;

        $this->assets[$containerKey][$index] = [];

        return $index;
    }

    public function addBlockToContainerAtPosition(Widget $block, string $containerKey, ?int $position = null): int
    {
        $blockIndex = $this->addBlockToContainer($block, $containerKey);

        if ($position === null || $position >= $blockIndex) {
            return $blockIndex;
        }

        $position = max(0, $position);

        $this->insertContainerBlockAtPosition($containerKey, $blockIndex, $position);

        return $position;
    }

    public function reorderBlocks(string $containerKey, string $containerBlockIndex, int $blockIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        [$originalContainer, $originalIndex] = explode('.', $containerBlockIndex);

        $originalIndex = (int) $originalIndex;

        $this->moveLoadedContainerBlock($originalContainer, $originalIndex, $containerKey, $blockIndex);

        $result = ReorderLayoutBlockAction::run(
            state: LayoutBuilderStateData::fromLivewire($this->containers, $this->assets, $this->originalAssets, $this->selectedRecords),
            originalContainer: $originalContainer,
            targetContainer: $containerKey,
            originalIndex: $originalIndex,
            targetIndex: $blockIndex,
        );

        $this->applyLayoutMutationResult($result);

        if (isset($this->containers[$containerKey]['widgets'][$blockIndex])) {
            $this->updatePageAssets($containerKey, $blockIndex);
        }
    }

    public function moveBlockUp(string $containerKey, int $blockIndex): void
    {
        if (! $this->canMoveBlockUp($containerKey, $blockIndex)) {
            return;
        }

        $this->reorderBlocks($containerKey, $containerKey . '.' . $blockIndex, $blockIndex - 1);
    }

    public function moveBlockDown(string $containerKey, int $blockIndex): void
    {
        if (! $this->canMoveBlockDown($containerKey, $blockIndex)) {
            return;
        }

        $this->reorderBlocks($containerKey, $containerKey . '.' . $blockIndex, $blockIndex + 1);
    }

    public function moveBlockToContainer(string $containerKey, int $blockIndex, string $targetContainerKey): void
    {
        if (! $this->canMoveBlockToContainer($containerKey, $blockIndex, $targetContainerKey)) {
            return;
        }

        $targetIndex = count($this->containers[$targetContainerKey]['widgets']);

        $this->reorderBlocks($targetContainerKey, $containerKey . '.' . $blockIndex, $targetIndex);
    }

    public function canMoveBlockUp(string $containerKey, int $blockIndex): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['widgets'][$blockIndex])
            && $blockIndex > 0;
    }

    public function canMoveBlockDown(string $containerKey, int $blockIndex): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['widgets'][$blockIndex])
            && $blockIndex < count($this->containers[$containerKey]['widgets']) - 1;
    }

    public function canMoveBlockToContainer(string $containerKey, int $blockIndex, string $targetContainerKey): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['widgets'][$blockIndex])
            && isset($this->containers[$targetContainerKey])
            && $containerKey !== $targetContainerKey;
    }

    public function canMoveBlockToAnotherContainer(string $containerKey, int $blockIndex): bool
    {
        $this->ensureLoaded();

        return isset($this->containers[$containerKey]['widgets'][$blockIndex])
            && collect($this->containers)
                ->keys()
                ->contains(fn (string $targetContainerKey): bool => $targetContainerKey !== $containerKey);
    }

    public function resolveAdminBlockPreviewData(string $containerKey, int $blockIndex): AdminBlockPreviewData
    {
        $block = $this->getContainerBlock($containerKey, $blockIndex);

        return ResolveAdminBlockPreviewDataAction::run(
            block: $block,
            containerBlock: $this->containers[$containerKey]['widgets'][$blockIndex],
            page: $this->page,
            assetCount: $this->countBlockAssets($containerKey, $blockIndex),
            hasPageAssets: $this->hasPageAssets($containerKey, $blockIndex),
        );
    }

    public function resolveAdminBlockPreviewView(AdminBlockPreviewData $previewData): string
    {
        return $previewData->view;
    }

    public function duplicateBlock(string $containerKey, int $originalIndex, bool $withAssets = true): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerBlock = $this->containers[$containerKey]['widgets'][$originalIndex];

        $containerBlock['occurrence'] = $this->getLastContainerBlockOccurrence($containerKey, $containerBlock['widget_key']) + 1;

        $this->containers[$containerKey]['widgets'][] = $containerBlock;

        $this->containerBlocks[$containerKey][] = $this->getContainerBlock($containerKey, $originalIndex);
        $blockIndex = array_key_last($this->containerBlocks[$containerKey]);

        if ($withAssets) {
            $this->assets[$containerKey][$blockIndex] = $this->assets[$containerKey][$originalIndex];
        }

        $this->layoutUpdated();
    }

    public function removeBlock(string $containerKey, int $blockIndex): void
    {
        $this->assertCanUpdateLayout();

        if (isset($this->containers[$containerKey]['widgets'][$blockIndex])) {
            unset($this->containers[$containerKey]['widgets'][$blockIndex]);
            $this->containers[$containerKey]['widgets'] = array_values($this->containers[$containerKey]['widgets']);
        }

        if (isset($this->containerBlocks[$containerKey][$blockIndex])) {
            unset($this->containerBlocks[$containerKey][$blockIndex]);
            $this->containerBlocks[$containerKey] = array_values($this->containerBlocks[$containerKey]);
        }

        if (isset($this->assets[$containerKey][$blockIndex])) {
            unset($this->assets[$containerKey][$blockIndex]);
            $this->assets[$containerKey] = array_values($this->assets[$containerKey]);
        }

        if (isset($this->selectedRecords[$containerKey][$blockIndex])) {
            unset($this->selectedRecords[$containerKey][$blockIndex]);
            $this->selectedRecords[$containerKey] = array_values($this->selectedRecords[$containerKey]);
        }

        $this->layoutUpdated();
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function editLayoutBlock(string $containerKey, int $blockIndex, array $data): void
    {
        $this->ensureLoaded();

        $this->containers[$containerKey]['widgets'][$blockIndex]['meta'] = array_merge(
            $this->containers[$containerKey]['widgets'][$blockIndex]['meta'] ?? [],
            $data,
        );

        $this->layoutUpdated();
    }

    public function getContainerBlock(string $containerKey, int $blockIndex): Widget
    {
        if (! isset($this->containerBlocks[$containerKey][$blockIndex])) {
            $this->ensureLoaded();
        }

        if (! isset($this->containerBlocks[$containerKey][$blockIndex])) {
            $block = $this->loadBlock($containerKey, $blockIndex, withAssets: false);

            $assets = $this->loadBlockAssetsFor($block, $containerKey, $blockIndex);

            $block->setRelation('assets', $assets);
        }

        return $this->containerBlocks[$containerKey][$blockIndex];
    }

    public function getContainerBlockConfigurator(string $containerKey, int $blockIndex): ?string
    {
        return $this->getContainerBlock($containerKey, $blockIndex)?->type->admin['layout_block_configurator']
            ?? null;
    }

    public function getContainerBlockOccurrence(string $containerKey, int $blockIndex): int
    {
        return (int) ($this->containers[$containerKey]['widgets'][$blockIndex]['occurrence'] ?? 1);
    }

    protected function moveContainerBlock(string $originalContainer, int $originalIndex, string $containerKey, int $blockIndex): void
    {
        $block = $this->getContainerBlock($originalContainer, $originalIndex);

        $containerBlock = $this->containers[$originalContainer]['widgets'][$originalIndex];

        if ($originalContainer !== $containerKey) {
            $containerBlock['occurrence'] = $this->getLastContainerBlockOccurrence(
                containerKey: $containerKey,
                widgetKey: $containerBlock['widget_key'],
                blocks: $this->containers[$containerKey]['widgets'],
            ) + 1;
        }

        $blocks = $this->containers[$originalContainer]['widgets'];

        unset($blocks[$originalIndex]);

        $this->containers[$originalContainer]['widgets'] = array_values($blocks);

        $blocks = $this->containers[$containerKey]['widgets'];
        $blocks = array_merge(array_slice($blocks, 0, $blockIndex), [$containerBlock], array_slice($blocks, $blockIndex));
        $this->containers[$containerKey]['widgets'] = $blocks;

        if ($containerKey !== $originalContainer) {
            unset($this->containerBlocks[$originalContainer][$originalIndex]);
            $this->containerBlocks[$originalContainer] = array_values($this->containerBlocks[$originalContainer]);
        }

        $containerBlocks = $this->containerBlocks[$containerKey] ?? [];
        $containerBlocks = array_merge(array_slice($containerBlocks, 0, $blockIndex), [$block], array_slice($containerBlocks, $blockIndex));
        $this->containerBlocks[$containerKey] = $containerBlocks;

        $this->originalAssets ??= [];

        $originalContainerBlockAssets = $this->originalAssets[$originalContainer][$originalIndex] ?? [];

        if ($containerKey !== $originalContainer && isset($this->originalAssets[$originalContainer][$originalIndex])) {
            unset($this->originalAssets[$originalContainer][$originalIndex]);
            $this->originalAssets[$originalContainer] = array_values($this->originalAssets[$originalContainer]);
        }

        $targetOriginalAssets = $this->originalAssets[$containerKey] ?? [];
        $targetOriginalAssets = array_merge(
            array_slice($targetOriginalAssets, 0, $blockIndex),
            [$originalContainerBlockAssets],
            array_slice($targetOriginalAssets, $blockIndex),
        );
        $this->originalAssets[$containerKey] = $targetOriginalAssets;

        $this->updatePageAssets($containerKey, $blockIndex);
    }

    protected function insertContainerBlockAtPosition(string $containerKey, int $originalIndex, int $position): void
    {
        if (isset($this->containers[$containerKey]['widgets'][$originalIndex])) {
            $this->containers[$containerKey]['widgets'] = $this->insertArrayItemAtPosition(
                $this->containers[$containerKey]['widgets'],
                $originalIndex,
                $position,
            );
        }

        foreach (['containerBlocks', 'assets', 'originalAssets', 'selectedRecords'] as $property) {
            if (! isset($this->{$property}[$containerKey][$originalIndex])) {
                continue;
            }

            $this->{$property}[$containerKey] = $this->insertArrayItemAtPosition(
                $this->{$property}[$containerKey],
                $originalIndex,
                $position,
            );
        }

        $this->updatePageAssets($containerKey, $position);
    }

    /**
     * @param  array<array-key, mixed>  $items
     * @return array<array-key, mixed>
     */
    protected function insertArrayItemAtPosition(array $items, int $originalIndex, int $position): array
    {
        $item = $items[$originalIndex];

        unset($items[$originalIndex]);

        $items = array_values($items);

        return array_merge(array_slice($items, 0, $position), [$item], array_slice($items, $position));
    }

    protected function moveLoadedContainerBlock(string $originalContainer, int $originalIndex, string $containerKey, int $blockIndex): void
    {
        if (! isset($this->containerBlocks[$originalContainer][$originalIndex])) {
            return;
        }

        $block = $this->containerBlocks[$originalContainer][$originalIndex];

        unset($this->containerBlocks[$originalContainer][$originalIndex]);
        $this->containerBlocks[$originalContainer] = array_values($this->containerBlocks[$originalContainer]);

        $containerBlocks = $this->containerBlocks[$containerKey] ?? [];
        $blockIndex = min(count($containerBlocks), max(0, $blockIndex));

        $this->containerBlocks[$containerKey] = array_merge(
            array_slice($containerBlocks, 0, $blockIndex),
            [$block],
            array_slice($containerBlocks, $blockIndex),
        );
    }

    protected function normalizeContainerBlockOccurrences(string $containerKey): void
    {
        if (! isset($this->containers[$containerKey]['widgets'])) {
            return;
        }

        $occurrences = [];

        foreach ($this->containers[$containerKey]['widgets'] as $blockIndex => $containerBlock) {
            $widgetKey = $containerBlock['widget_key'];
            $occurrences[$widgetKey] = ($occurrences[$widgetKey] ?? 0) + 1;
            $occurrence = $occurrences[$widgetKey];

            $this->containers[$containerKey]['widgets'][$blockIndex]['occurrence'] = $occurrence;

            foreach (['assets', 'originalAssets'] as $property) {
                foreach (array_keys($this->{$property}[$containerKey][$blockIndex] ?? []) as $assetIndex) {
                    $this->{$property}[$containerKey][$blockIndex][$assetIndex]['occurrence'] = $occurrence;
                }
            }
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getContainerWidgetKeys(): array
    {
        return collect($this->containers)
            ->pluck('widgets.*.widget_key')
            ->flatten()
            ->unique()
            ->toArray();
    }

    /**
     * @param  array<array-key, mixed>  $blocks
     */
    protected function getLastContainerBlockOccurrence(string $containerKey, string $widgetKey, ?int $compareIndex = null, ?array $blocks = null): int
    {
        if ($blocks === null || $blocks === []) {
            $blocks = $this->containers[$containerKey]['widgets'];
        }

        $occurrence = 1;

        foreach ($blocks as $blockIndex => $block) {
            if ($compareIndex !== null && $blockIndex === $compareIndex) {
                return $occurrence;
            }

            if ($block['widget_key'] === $widgetKey) {
                $occurrence++;
            }
        }

        return $occurrence;
    }

    protected function loadBlock(string $containerKey, int $blockIndex, bool $withAssets = true): Widget
    {
        $container = $this->containers[$containerKey] ?? null;

        throw_if($container === null || ! isset($container['widgets'][$blockIndex]), Exception::class, 'Container block not found for container: ' . $containerKey . ' index: ' . $blockIndex);

        $containerBlock = $container['widgets'][$blockIndex];
        $widgetKey = $containerBlock['widget_key'];
        $occurrence = $containerBlock['occurrence'] ?? 1;

        $block = $this->getBlock($widgetKey);

        if ($withAssets) {
            $block->setRelation('assets', $this->loadBlockAssets($block, $containerKey, $occurrence));
        }

        $this->containerBlocks[$containerKey][$blockIndex] = $block;

        return $block;
    }

    /**
     * @param  array<array-key, mixed>  $allBlockAssets
     * @param  array<array-key, mixed>  $allBlocks
     */
    protected function setupContainerBlocks(string $containerKey, array $allBlocks, ?array $allBlockAssets = null): void
    {
        $container = $this->containers[$containerKey];

        $blockOccurrences = [];

        foreach ($container['widgets'] as $blockIndex => $containerBlock) {
            $widgetKey = $containerBlock['widget_key'];
            $oldContainerKey = $containerBlock['old_container'] ?? null;

            throw_unless(isset($allBlocks[$widgetKey]), Exception::class, 'Widget not found for key: ' . $widgetKey);

            /** @var Widget $block */
            $block = clone $allBlocks[$widgetKey];

            if (! isset($blockOccurrences[$widgetKey])) {
                $blockOccurrences[$widgetKey] = 1;
            } else {
                $blockOccurrences[$widgetKey]++;
            }

            $blockOccurrence = $blockOccurrences[$widgetKey];

            $this->containers[$containerKey]['widgets'][$blockIndex]['occurrence'] = $blockOccurrence;

            if ($allBlockAssets !== null) {
                $assets = $allBlockAssets[$containerKey][$blockIndex] ?? new Collection;
            } elseif ($block->relationLoaded('assets')) {
                $assets = $block->assets;
            } else {
                $assets = $this->loadBlockAssets($block, $containerKey, $blockOccurrence);
            }

            $block->setRelation(
                'assets',
                $this->filterContainerBlockAssets($assets, $oldContainerKey ?? $containerKey, $blockOccurrence, $block),
            );

            $this->containerBlocks[$containerKey][$blockIndex] = $block;

            $this->assets[$containerKey][$blockIndex] = $this->mapBlockAssets($block, $containerKey, $oldContainerKey);

            $this->updatePageAssets($containerKey, $blockIndex);
        }
    }

    /**
     * @throws Exception
     */
    protected function getBlock(int|string $id, bool $withRelations = true): Widget
    {
        $query = $this->getBlockQuery(withRelations: $withRelations);

        if (is_numeric($id)) {
            $query->whereKey($id);
        } else {
            $query->where('key', $id);
        }

        /** @var Widget|null $block */
        $block = $query->first();

        throw_unless($block, Exception::class, sprintf("Unable to find '%s' block", (string) $id));

        return $block;
    }

    /**
     * @return EloquentBuilder<Widget>
     */
    protected function getBlockQuery(bool $withRelations = true): EloquentBuilder
    {
        /** @var class-string<Widget> $model */
        $model = Widget::class;

        return $model::query()
            ->when(
                $withRelations,
                fn (EloquentBuilder $query) => $query
                    /** @phpstan-ignore-next-line Widget exposes this local scope through Eloquent. */
                    ->withLayoutsCount()
                    ->withCount([
                        'blockPageAssets as page_assets_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->distinct(['pageable_id', 'pageable_type'])
                            ->when(
                                $this->inPageContext(),
                                fn (EloquentBuilder $query) => $query->where([
                                    'pageable_type' => $this->page->getMorphClass(),
                                    'pageable_id' => $this->page->getKey(),
                                ]),
                            ),
                    ])
                    ->with([
                        'type',
                        'backgroundImage',
                        'image',
                        'translation' => fn (BuilderContract $query): BuilderContract => $query->orderBy('language_id'),
                    ]),
            );
    }

    /**
     * @return EloquentBuilder<Widget>
     */
    protected function getBlockDisplayQuery(): EloquentBuilder
    {
        return $this->getBlockQuery(withRelations: false)
            /** @phpstan-ignore-next-line Widget exposes this local scope through Eloquent. */
            ->withLayoutsCount()
            ->withCount([
                'blockPageAssets as page_assets_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->distinct(['pageable_id', 'pageable_type'])
                    ->when(
                        $this->inPageContext(),
                        fn (EloquentBuilder $query) => $query->where([
                            'pageable_type' => $this->page->getMorphClass(),
                            'pageable_id' => $this->page->getKey(),
                        ]),
                    ),
            ])
            ->with([
                'assets' => fn (BuilderContract $query): BuilderContract => $query->when(
                    $this->page,
                    fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                        fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                            'pageable_id' => $this->page->getKey(),
                            'pageable_type' => $this->page->getMorphClass(),
                        ])
                            ->orWhereNull(['pageable_type', 'pageable_id']),
                    ),
                    fn (EloquentBuilder $query): EloquentBuilder => $query->whereNull(['pageable_id', 'pageable_type']),
                )
                    ->whereIn('workspace_id', $this->getCurrentContainerBlockAssetWorkspaceIds())
                    ->ordered()
                    ->with(
                        'asset',
                        fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
                    ),
                'type',
                'translation' => fn (BuilderContract $query): BuilderContract => $query->orderBy('language_id'),
            ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function preloadAllBlocks(bool $withAssets = true): array
    {
        $widgetKeys = $this->getContainerWidgetKeys();

        if ($widgetKeys === []) {
            return [];
        }

        $allBlockAssets = $this->getBlockQuery()
            ->whereIn('key', $widgetKeys)
            ->when(
                $withAssets,
                fn (EloquentBuilder $query): EloquentBuilder => $query->with([
                    'assets' => fn (BuilderContract $query): BuilderContract => $query->when(
                        $this->page,
                        fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                            fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                                'pageable_id' => $this->page->getKey(),
                                'pageable_type' => $this->page->getMorphClass(),
                            ])
                                ->orWhereNull(['pageable_type', 'pageable_id']),
                        ),
                        fn (EloquentBuilder $query): EloquentBuilder => $query->whereNull(['pageable_id', 'pageable_type']),
                    )
                        ->whereIn('workspace_id', $this->getCurrentContainerBlockAssetWorkspaceIds())
                        ->ordered()
                        ->with(
                            'asset',
                            fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
                        ),
                ]),
            )
            ->get()
            ->keyBy('key')
            ->all();

        if ($withAssets) {
            foreach ($allBlockAssets as $blockAssets) {
                $hasPageAssets = $blockAssets->assets->whereNotNull(['pageable_type', 'pageable_id'])->isNotEmpty();

                if ($hasPageAssets) {
                    $blockAssets->setRelation('assets', $blockAssets->assets->filter(
                        fn (WidgetAsset $asset): bool => $asset->pageable_type !== null && $asset->pageable_id !== null,
                    ));
                }
            }
        }

        return $allBlockAssets;
    }
}
