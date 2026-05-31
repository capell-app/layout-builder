<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\DefaultBlockConfigurator;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Livewire\Filament\Support\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Component;

final class LayoutBuilderActionFlowHarness extends LayoutBuilder
{
    /**
     * @var array<int, array{method: string, arguments: array<array-key, mixed>}>
     */
    public array $recordedCalls = [];

    public bool $saveLayoutResult = true;

    public bool $hasPageAssetsResult = false;

    public bool $pageContextResult = true;

    /**
     * @var array<string, string>
     */
    public array $containerOptionValues = [
        'main' => 'Main',
        'aside' => 'Aside',
    ];

    /**
     * @var array<array-key, mixed>
     */
    public array $selectedAssetValues = ['page.10'];

    public ?int $blockAssetCountResult = 1;

    public ?Widget $containerBlockRecord = null;

    public bool $blockHasPageAssetsResult = false;

    public bool $blockHasGlobalAssetsResult = true;

    public ?Schema $mountedActionSchemaForTest = null;

    #[Override]
    public function saveLayout(bool $withNotifications = false): bool
    {
        $this->record('saveLayout', [$withNotifications]);

        return $this->saveLayoutResult;
    }

    #[Override]
    public function layoutUpdated(bool $modified = true): void
    {
        $this->record('layoutUpdated', [$modified]);
    }

    #[Override]
    public function undoLayoutMutation(): void
    {
        $this->record('undoLayoutMutation');
    }

    #[Override]
    public function redoLayoutMutation(): void
    {
        $this->record('redoLayoutMutation');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    #[Override]
    public function saveContainer(array $data, ?string $key = null, ?int $position = null): void
    {
        $this->record('saveContainer', [$data, $key, $position]);
    }

    #[Override]
    public function removeContainer(string $containerKey): void
    {
        $this->record('removeContainer', [$containerKey]);
    }

    #[Override]
    public function moveContainerUp(string $containerKey): void
    {
        $this->record('moveContainerUp', [$containerKey]);
    }

    #[Override]
    public function moveContainerDown(string $containerKey): void
    {
        $this->record('moveContainerDown', [$containerKey]);
    }

    #[Override]
    public function duplicateContainer(string $containerKey): void
    {
        $this->record('duplicateContainer', [$containerKey]);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    #[Override]
    public function editLayoutBlock(string $containerKey, int $blockIndex, array $data): void
    {
        $this->record('editLayoutBlock', [$containerKey, $blockIndex, $data]);
    }

    /**
     * @param  array<int, int|string>  $blocks
     */
    #[Override]
    public function addBlocksToContainer(string $containerKey, array $blocks, ?string $actionModalId = null, ?int $position = null): void
    {
        $this->record('addBlocksToContainer', [$containerKey, $blocks, $actionModalId, $position]);
    }

    #[Override]
    public function duplicateBlock(string $containerKey, int $originalIndex, bool $withAssets = true): void
    {
        $this->record('duplicateBlock', [$containerKey, $originalIndex, $withAssets]);
    }

    #[Override]
    public function moveBlockUp(string $containerKey, int $blockIndex): void
    {
        $this->record('moveBlockUp', [$containerKey, $blockIndex]);
    }

    #[Override]
    public function moveBlockDown(string $containerKey, int $blockIndex): void
    {
        $this->record('moveBlockDown', [$containerKey, $blockIndex]);
    }

    #[Override]
    public function moveBlockToContainer(string $containerKey, int $blockIndex, string $targetContainerKey): void
    {
        $this->record('moveBlockToContainer', [$containerKey, $blockIndex, $targetContainerKey]);
    }

    #[Override]
    public function removeBlock(string $containerKey, int $blockIndex): void
    {
        $this->record('removeBlock', [$containerKey, $blockIndex]);
    }

    /**
     * @return Collection<string, string>
     */
    #[Override]
    public function getContainerOptions(): Collection
    {
        return collect($this->containerOptionValues);
    }

    /**
     * @return array<int, TextInput>
     */
    #[Override]
    public function getContainerSchema(Schema $configurator, array $arguments): array
    {
        $this->record('getContainerSchema', [$configurator->getOperation(), $arguments]);

        return [
            TextInput::make('key'),
        ];
    }

    #[Override]
    public function getContainerBlockConfigurator(string $containerKey, int $blockIndex): string
    {
        $this->record('getContainerBlockConfigurator', [$containerKey, $blockIndex]);

        return DefaultBlockConfigurator::getKey();
    }

    #[Override]
    public function canMoveContainerUp(string $containerKey): bool
    {
        $this->record('canMoveContainerUp', [$containerKey]);

        return true;
    }

    #[Override]
    public function canMoveContainerDown(string $containerKey): bool
    {
        $this->record('canMoveContainerDown', [$containerKey]);

        return true;
    }

    #[Override]
    public function canMoveBlockUp(string $containerKey, int $blockIndex): bool
    {
        $this->record('canMoveBlockUp', [$containerKey, $blockIndex]);

        return true;
    }

    #[Override]
    public function canMoveBlockDown(string $containerKey, int $blockIndex): bool
    {
        $this->record('canMoveBlockDown', [$containerKey, $blockIndex]);

        return true;
    }

    #[Override]
    public function canMoveBlockToAnotherContainer(string $containerKey, int $blockIndex): bool
    {
        $this->record('canMoveBlockToAnotherContainer', [$containerKey, $blockIndex]);

        return true;
    }

    #[Override]
    public function canMoveAssetUp(string $containerKey, int $blockIndex, int $assetIndex): bool
    {
        $this->record('canMoveAssetUp', [$containerKey, $blockIndex, $assetIndex]);

        return true;
    }

    #[Override]
    public function canMoveAssetDown(string $containerKey, int $blockIndex, int $assetIndex): bool
    {
        $this->record('canMoveAssetDown', [$containerKey, $blockIndex, $assetIndex]);

        return true;
    }

    #[Override]
    public function countBlockAssets(string $containerKey, int $blockIndex): int
    {
        $this->record('countBlockAssets', [$containerKey, $blockIndex]);

        return $this->blockAssetCountResult ?? count($this->assets[$containerKey][$blockIndex] ?? []);
    }

    #[Override]
    public function hasPageAssets(string $containerKey, int $blockIndex): bool
    {
        $this->record('hasPageAssets', [$containerKey, $blockIndex]);

        return $this->hasPageAssetsResult;
    }

    #[Override]
    public function inPageContext(): bool
    {
        return $this->pageContextResult;
    }

    /**
     * @param  array{containerKey: string, blockIndex: int, hasPageAssets?: bool}  $arguments
     * @param  array<int, int|string>  $assets
     */
    #[Override]
    public function addAssetsToBlock(array $arguments, string $type, array $assets): void
    {
        $this->record('addAssetsToBlock', [$arguments, $type, $assets]);
    }

    #[Override]
    public function moveAssetUp(string $containerKey, int $blockIndex, int $assetIndex): void
    {
        $this->record('moveAssetUp', [$containerKey, $blockIndex, $assetIndex]);
    }

    #[Override]
    public function moveAssetDown(string $containerKey, int $blockIndex, int $assetIndex): void
    {
        $this->record('moveAssetDown', [$containerKey, $blockIndex, $assetIndex]);
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function getSelectedAssets(string $containerKey, int $blockIndex): array
    {
        $this->record('getSelectedAssets', [$containerKey, $blockIndex]);

        return $this->selectedAssetValues;
    }

    #[Override]
    public function removeSelectedAssets(string $containerKey, int $blockIndex): void
    {
        $this->record('removeSelectedAssets', [$containerKey, $blockIndex]);
    }

    #[Override]
    public function ensureLoaded(): void
    {
        $this->record('ensureLoaded');
    }

    #[Override]
    public function assertCanUpdateLayout(): void
    {
        $this->record('assertCanUpdateLayout');
    }

    #[Override]
    public function assertCanEditContent(): void
    {
        $this->record('assertCanEditContent');
    }

    #[Override]
    public function canEditContent(): bool
    {
        return true;
    }

    #[Override]
    public function loadFromStore(): void
    {
        $this->record('loadFromStore');
    }

    #[Override]
    public function getCurrentBlockAssetWorkspaceId(?Widget $block = null): int
    {
        unset($block);

        return 0;
    }

    #[Override]
    public function getContainerBlockOccurrence(string $containerKey, int $blockIndex): int
    {
        $this->record('getContainerBlockOccurrence', [$containerKey, $blockIndex]);

        return 1;
    }

    #[Override]
    public function getLayoutBuilderMountedActionSchema(): ?Schema
    {
        return $this->mountedActionSchemaForTest;
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function getBlockAssetsByType(string $containerKey, int $blockIndex, string $type): array
    {
        $this->record('getBlockAssetsByType', [$containerKey, $blockIndex, $type]);

        return parent::getBlockAssetsByType($containerKey, $blockIndex, $type);
    }

    #[Override]
    public function blockHasPageAssets(Widget $block): bool
    {
        $this->record('blockHasPageAssets', [$block->getKey()]);

        return $this->blockHasPageAssetsResult;
    }

    #[Override]
    public function blockHasGlobalAssets(Widget $block): bool
    {
        $this->record('blockHasGlobalAssets', [$block->getKey()]);

        return $this->blockHasGlobalAssetsResult;
    }

    /**
     * @return array<class-string, array<int, string>>
     */
    #[Override]
    public function getAssetRelations(): array
    {
        return [];
    }

    #[Override]
    public function reloadContainerBlockAsset(string $containerKey, int $blockIndex, int $index): void
    {
        $this->record('reloadContainerBlockAsset', [$containerKey, $blockIndex, $index]);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    #[Override]
    public function updateBlockAssetContentState(string $containerKey, int $blockIndex, int $index, array $data): void
    {
        $this->record('updateBlockAssetContentState', [$containerKey, $blockIndex, $index, $data]);

        parent::updateBlockAssetContentState($containerKey, $blockIndex, $index, $data);
    }

    #[Override]
    public function togglePageAssets(string $containerKey, int $blockIndex, mixed $page): void
    {
        $this->record('togglePageAssets', [$containerKey, $blockIndex, $page]);
    }

    #[Override]
    public function getContainerBlock(string $containerKey, int $blockIndex): Widget
    {
        $this->record('getContainerBlock', [$containerKey, $blockIndex]);

        if ($this->containerBlockRecord instanceof Widget) {
            return $this->containerBlockRecord;
        }

        $this->containerBlockRecord = Widget::factory()->create([
            'name' => 'Hero block',
            'key' => 'hero',
            'admin' => [
                'asset_types' => ['Page'],
            ],
        ]);
        $this->containerBlockRecord->setRelation('assets', new EloquentCollection);

        return $this->containerBlockRecord;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function record(string $method, array $arguments = []): void
    {
        $this->recordedCalls[] = [
            'method' => $method,
            'arguments' => $arguments,
        ];
    }
}

final class LayoutBuilderMutationHarness extends LayoutBuilder
{
    /** @var array<int, string> */
    public array $events = [];

    /**
     * @param  array<array-key, mixed>  $containerBlocks
     */
    public function seedContainerBlocks(array $containerBlocks): void
    {
        $this->containerBlocks = $containerBlocks;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function containerBlocksForTest(): array
    {
        return $this->containerBlocks;
    }

    public function setupContainersFromLayoutForTest(): void
    {
        $this->setupContainers();
    }

    public function insertContainerBlockAtPositionForTest(string $containerKey, int $originalIndex, int $position): void
    {
        $this->insertContainerBlockAtPosition($containerKey, $originalIndex, $position);
    }

    public function normalizeContainerBlockOccurrencesForTest(string $containerKey): void
    {
        $this->normalizeContainerBlockOccurrences($containerKey);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function containerWidgetKeysForTest(): array
    {
        return $this->getContainerWidgetKeys();
    }

    #[Override]
    public function assertCanUpdateLayout(): void
    {
        $this->events[] = 'assertCanUpdateLayout';
    }

    #[Override]
    public function ensureLoaded(): void
    {
        $this->events[] = 'ensureLoaded';
    }

    #[Override]
    public function layoutUpdated(bool $modified = true): void
    {
        $this->layoutModified = $modified;
        $this->events[] = 'layoutUpdated';
    }
}

it('builds every layout builder action with stable names', function (): void {
    $layoutBuilder = new LayoutBuilder;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = ['main' => []];
    $layoutBuilder->selectedRecords = ['main' => []];

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    $actions = [
        $factory->saveLayoutAction(),
        $factory->duplicateLayoutAction(),
        $factory->cloneLayoutForPageAction(),
        $factory->undoLayoutMutationAction(),
        $factory->redoLayoutMutationAction(),
        $factory->addContainerAction(),
        $factory->editContainerAction(),
        $factory->removeContainerAction(),
        $factory->moveContainerUpAction(),
        $factory->moveContainerDownAction(),
        $factory->duplicateContainerAction(),
        $factory->editLayoutBlockAction(),
        $factory->addBlockAction(),
        $factory->editBlockAction(),
        $factory->duplicateBlockAction(),
        $factory->moveBlockUpAction(),
        $factory->moveBlockDownAction(),
        $factory->moveBlockToContainerAction(),
        $factory->removeBlockAction(),
        $factory->selectAssetAction(),
        $factory->addAssetAction(),
        $factory->editBlockAssetAction(),
        $factory->moveAssetUpAction(),
        $factory->moveAssetDownAction(),
        $factory->removeAssetsAction(),
        $factory->changeLayoutAction(),
        $factory->togglePageAssetsAction(),
    ];

    expect($actions)
        ->each->toBeInstanceOf(Action::class)
        ->and(array_map(
            static fn (Action $action): string => $action->getName() ?? '',
            $actions,
        ))->toBe([
            'saveLayout',
            'duplicateLayout',
            'cloneLayoutForPage',
            'undoLayoutMutation',
            'redoLayoutMutation',
            'addContainer',
            'editContainer',
            'removeContainer',
            'moveContainerUp',
            'moveContainerDown',
            'duplicateContainer',
            'editLayoutBlock',
            'addBlock',
            'editBlock',
            'duplicateBlock',
            'moveBlockUp',
            'moveBlockDown',
            'moveBlockToContainer',
            'removeBlock',
            'selectAsset',
            'addAsset',
            'editBlockAsset',
            'moveAssetUp',
            'moveAssetDown',
            'removeAssets',
            'changeLayout',
            'togglePageAssets',
        ]);
});

it('runs layout builder action closures through the expected editor workflow methods', function (): void {
    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = ['main' => [[]]];
    $layoutBuilder->selectedRecords = ['main' => [[]]];

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    invokeLayoutBuilderAction($factory->saveLayoutAction(), $layoutBuilder);
    invokeLayoutBuilderAction(
        $factory->addContainerAction(),
        $layoutBuilder,
        data: ['key' => 'hero', 'meta' => ['area' => 'main']],
        arguments: ['position' => 0],
    );
    invokeLayoutBuilderAction(
        $factory->editContainerAction(),
        $layoutBuilder,
        data: ['key' => 'primary', 'meta' => []],
        arguments: ['containerKey' => 'main'],
    );
    invokeLayoutBuilderAction($factory->removeContainerAction(), $layoutBuilder, arguments: ['containerKey' => 'aside']);
    invokeLayoutBuilderAction($factory->moveContainerUpAction(), $layoutBuilder, arguments: ['containerKey' => 'main']);
    invokeLayoutBuilderAction($factory->moveContainerDownAction(), $layoutBuilder, arguments: ['containerKey' => 'main']);
    invokeLayoutBuilderAction($factory->duplicateContainerAction(), $layoutBuilder, arguments: ['containerKey' => 'main']);
    invokeLayoutBuilderAction(
        $factory->editLayoutBlockAction(),
        $layoutBuilder,
        data: ['html_class' => 'featured'],
        arguments: ['containerKey' => 'main', 'blockIndex' => 0],
    );
    invokeLayoutBuilderAction(
        $factory->addBlockAction(),
        $layoutBuilder,
        data: ['widgets' => [10, 20], 'container' => 'aside'],
        arguments: ['position' => 1],
    );
    invokeLayoutBuilderAction($factory->duplicateBlockAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);
    invokeLayoutBuilderAction($factory->moveBlockUpAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 1]);
    invokeLayoutBuilderAction($factory->moveBlockDownAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);
    invokeLayoutBuilderAction(
        $factory->moveBlockToContainerAction(),
        $layoutBuilder,
        data: ['target_container' => 'aside'],
        arguments: ['containerKey' => 'main', 'blockIndex' => 0],
    );
    invokeLayoutBuilderAction($factory->removeBlockAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);
    invokeLayoutBuilderAction(
        $factory->selectAssetAction(),
        $layoutBuilder,
        data: ['assets' => [123]],
        arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'type' => 'Page'],
    );
    invokeLayoutBuilderAction($factory->moveAssetUpAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'assetIndex' => 1]);
    invokeLayoutBuilderAction($factory->moveAssetDownAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'assetIndex' => 0]);
    invokeLayoutBuilderAction($factory->removeAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);
    invokeLayoutBuilderAction($factory->togglePageAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);

    invokeLayoutBuilderAction($factory->undoLayoutMutationAction(), $layoutBuilder);
    invokeLayoutBuilderAction($factory->redoLayoutMutationAction(), $layoutBuilder);

    $calls = collect($layoutBuilder->recordedCalls)
        ->map(fn (array $call): string => $call['method'])
        ->all();

    expect($calls)->toContain(
        'saveLayout',
        'saveContainer',
        'removeContainer',
        'moveContainerUp',
        'moveContainerDown',
        'duplicateContainer',
        'editLayoutBlock',
        'addBlocksToContainer',
        'duplicateBlock',
        'moveBlockUp',
        'moveBlockDown',
        'moveBlockToContainer',
        'removeBlock',
        'addAssetsToBlock',
        'moveAssetUp',
        'moveAssetDown',
        'removeSelectedAssets',
        'togglePageAssets',
        'undoLayoutMutation',
        'redoLayoutMutation',
    );

    expect($layoutBuilder->recordedCalls[1])->toMatchArray([
        'method' => 'saveContainer',
        'arguments' => [
            ['key' => 'hero', 'meta' => ['area' => 'main']],
            null,
            0,
        ],
    ]);

    $addBlockCall = collect($layoutBuilder->recordedCalls)
        ->firstWhere('method', 'addBlocksToContainer');
    $addBlockCall = capell_test_array($addBlockCall);

    expect($addBlockCall['arguments'] ?? null)->toBe(['aside', [10, 20], null, 1]);

    $selectAssetCall = collect($layoutBuilder->recordedCalls)
        ->firstWhere('method', 'addAssetsToBlock');
    $selectAssetCall = capell_test_array($selectAssetCall);

    expect($selectAssetCall['arguments'] ?? null)->toBe([
        [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'hasPageAssets' => false,
        ],
        'Page',
        [123],
    ]);
});

it('evaluates layout builder action presentation and modal workflows from editor state', function (): void {
    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->layout = Layout::factory()->create(['name' => 'Current layout']);
    $layoutBuilder->page = Page::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'meta' => ['html_class' => 'lead']],
            ],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = [
        'main' => [
            [
                [
                    'asset_id' => $layoutBuilder->page->getKey(),
                    'asset_type' => 'Page',
                    'meta' => ['caption' => 'Hero image'],
                    'order' => 1,
                    'occurrence' => 1,
                    'pageable_id' => $layoutBuilder->page->getKey(),
                    'pageable_type' => $layoutBuilder->page->getMorphClass(),
                    'container' => 'main',
                ],
            ],
        ],
    ];
    $layoutBuilder->selectedRecords = ['main' => [['Page.' . $layoutBuilder->page->getKey()]]];

    $block = Widget::factory()->create([
        'name' => 'Hero block',
        'key' => 'hero',
        'admin' => ['asset_types' => ['Page']],
    ]);
    $blockAsset = WidgetAsset::query()->make([
        'widget_id' => $block->getKey(),
        'asset_id' => $layoutBuilder->page->getKey(),
        'asset_type' => 'Page',
        'meta' => ['caption' => 'Hero image'],
        'order' => 1,
        'occurrence' => 1,
    ]);
    $blockAsset->exists = true;
    $blockAsset->setRelation('asset', $layoutBuilder->page);

    $block->setRelation('assets', new EloquentCollection([$blockAsset]));
    $layoutBuilder->containerBlockRecord = $block;

    $factory = new LayoutBuilderActionFactory($layoutBuilder);
    $schema = layoutBuilderActionSchema($layoutBuilder);

    expect(layoutBuilderActionValue($factory->addBlockAction(), 'label', $layoutBuilder, arguments: []))->toBeString()
        ->and(layoutBuilderActionValue($factory->addBlockAction(), 'label', $layoutBuilder, arguments: ['position' => 1]))->toBeString()
        ->and(layoutBuilderActionValue($factory->addBlockAction(), 'schema', $layoutBuilder, arguments: [], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->addBlockAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main'], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->editContainerAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main'], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->editLayoutBlockAction(), 'isVisible', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]))->toBeTrue()
        ->and(layoutBuilderActionValue($factory->editLayoutBlockAction(), 'modalDescription', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]))->toBeString()
        ->and(layoutBuilderActionValue($factory->editLayoutBlockAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->selectAssetAction(), 'label', $layoutBuilder, arguments: ['type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->selectAssetAction(), 'modalHeading', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->selectAssetAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'type' => 'Page'], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->addAssetAction(), 'modalHeading', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->editBlockAssetAction(), 'modalHeading', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'index' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->editBlockAssetAction(), 'modalDescription', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0, 'index' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->togglePageAssetsAction(), 'isVisible', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]))->toBeTrue()
        ->and(layoutBuilderActionValue($factory->togglePageAssetsAction(), 'modalDescription', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]))->toBeString()
        ->and(layoutBuilderActionValue($factory->removeAssetsAction(), 'extraAttributes', $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]))->toHaveKey('x-show');

    try {
        invokeLayoutBuilderAction($factory->duplicateLayoutAction(), $layoutBuilder);
        invokeLayoutBuilderAction($factory->cloneLayoutForPageAction(), $layoutBuilder);
        invokeLayoutBuilderAction($factory->changeLayoutAction(), $layoutBuilder, data: ['layout_id' => Layout::factory()->create()->getKey()]);
    } catch (ActionNotResolvableException) {
        // These actions normally run while mounted by Filament; the layout mutation path above is still exercised.
    }

    $layoutBuilder->selectedAssetValues = [];

    try {
        invokeLayoutBuilderAction($factory->removeAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);
    } catch (Halt) {
        // The editor deliberately halts when the remove action is submitted with no selected assets.
    }

    invokeLayoutBuilderAction($factory->togglePageAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'blockIndex' => 0]);

    expect(collect($layoutBuilder->recordedCalls)->pluck('method')->all())->toContain(
        'assertCanUpdateLayout',
        'layoutUpdated',
        'getContainerSchema',
        'getContainerBlockConfigurator',
        'getBlockAssetsByType',
        'ensureLoaded',
        'togglePageAssets',
    );
});

it('builds change layout options from site-aware layout state', function (): void {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $currentLayout = Layout::factory()->site($site)->create(['name' => 'Current Site Layout']);
    $globalLayout = Layout::factory()->create(['name' => 'Global Layout', 'site_id' => null]);
    $otherSiteLayout = Layout::factory()->site($otherSite)->create(['name' => 'Other Site Layout']);
    Page::factory()->count(2)->site($site)->create(['layout_id' => $currentLayout->getKey()]);

    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->site = $site;
    $layoutBuilder->layout = $currentLayout;
    $layoutBuilder->page = Page::factory()->site($site)->create(['layout_id' => $currentLayout->getKey()]);

    $factory = new LayoutBuilderActionFactory($layoutBuilder);
    $schema = invokeLayoutBuilderActionFactoryMethod($factory, 'getChangeLayoutSchema');
    $select = $schema[0] ?? null;

    expect($select)->toBeInstanceOf(Select::class);

    /** @var Select $select */
    $options = $select->getOptions();

    expect($options)->toHaveKeys([$currentLayout->getKey(), $globalLayout->getKey()])
        ->and($options)->not->toHaveKey($otherSiteLayout->getKey())
        ->and($select->getDefaultState())->toBeInt();
});

it('adds and edits block assets through layout builder action callbacks', function (): void {
    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->layout = Layout::factory()->create(['name' => 'Asset workflow layout']);
    $layoutBuilder->page = Page::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'meta' => [], 'occurrence' => 1],
            ],
        ],
    ];
    $layoutBuilder->assets = ['main' => [[]]];
    $layoutBuilder->selectedRecords = ['main' => [[]]];

    $assetPage = Page::factory()->create(['name' => 'Hero asset page']);
    $block = Widget::factory()->create([
        'name' => 'Hero block',
        'key' => 'hero',
        'admin' => ['asset_types' => ['page']],
    ]);
    $block->setRelation('assets', new EloquentCollection);

    $layoutBuilder->containerBlockRecord = $block;

    $newBlockAsset = WidgetAsset::query()->make([
        'widget_id' => $block->getKey(),
        'workspace_id' => 0,
        'asset_type' => 'page',
        'asset_id' => $assetPage->getKey(),
        'meta' => [],
        'order' => 1,
        'occurrence' => 1,
    ]);
    $newBlockAsset->setRelation('asset', $assetPage);

    $layoutBuilder->mountedActionSchemaForTest = Schema::make(new LayoutBuilderActionSchemaHarness)
        ->record($newBlockAsset);

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    invokeLayoutBuilderAction(
        $factory->addAssetAction(),
        $layoutBuilder,
        data: [
            $assetPage->getKey() => ['caption' => 'Added through the action'],
        ],
        arguments: [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'type' => 'page',
        ],
    );

    $addedAssetState = $layoutBuilder->assets['main'][0][0] ?? [];

    $persistedAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($assetPage)
        ->create([
            'workspace_id' => 0,
            'container' => 'main',
            'occurrence' => 1,
            'order' => 1,
            'meta' => ['caption' => 'Before edit'],
            'pageable_id' => $layoutBuilder->page->getKey(),
            'pageable_type' => $layoutBuilder->page->getMorphClass(),
        ]);
    $persistedAsset->setRelation('asset', $assetPage);

    $block->setRelation('assets', new EloquentCollection([$persistedAsset]));
    $layoutBuilder->assets = [
        'main' => [
            [
                [
                    'id' => $persistedAsset->getKey(),
                    'widget_id' => $block->getKey(),
                    'workspace_id' => 0,
                    'asset_id' => $assetPage->getKey(),
                    'asset_type' => $assetPage->getMorphClass(),
                    'meta' => ['caption' => 'Before edit'],
                    'order' => 1,
                    'occurrence' => 1,
                    'pageable_id' => $layoutBuilder->page->getKey(),
                    'pageable_type' => $layoutBuilder->page->getMorphClass(),
                    'container' => 'main',
                ],
            ],
        ],
    ];

    $editSchema = Schema::make(new LayoutBuilderActionSchemaHarness)
        ->record($persistedAsset);
    $editAction = $factory->editBlockAssetAction();

    invokeLayoutBuilderBlockAssetEditAction(
        $editAction,
        $layoutBuilder,
        $persistedAsset,
        $editSchema,
        data: ['meta' => ['caption' => 'Edited through the action']],
        arguments: [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'index' => 0,
            'type' => $assetPage->getMorphClass(),
            'contentInventorySignature' => $layoutBuilder->contentInventorySignature(),
        ],
    );

    $calls = collect($layoutBuilder->recordedCalls)->pluck('method')->all();

    expect($layoutBuilder->assets['main'][0][0])->toMatchArray([
        'meta' => ['caption' => 'Edited through the action'],
    ])
        ->and($addedAssetState)->toMatchArray([
            'asset_id' => $assetPage->getKey(),
            'asset_type' => 'page',
            'meta' => ['caption' => 'Added through the action'],
            'pageable_id' => $layoutBuilder->page->getKey(),
            'pageable_type' => $layoutBuilder->page->getMorphClass(),
            'container' => 'main',
        ])
        ->and($persistedAsset->fresh()?->meta)->toBe(['caption' => 'Edited through the action'])
        ->and($layoutBuilder->assets['main'][0][0]['meta'])->toBe(['caption' => 'Edited through the action'])
        ->and($calls)->toContain(
            'assertCanUpdateLayout',
            'loadFromStore',
            'assertCanEditContent',
            'getContainerBlock',
            'getContainerBlockOccurrence',
            'updateBlockAssetContentState',
            'reloadContainerBlockAsset',
            'layoutUpdated',
        );
});

it('mutates real layout builder container and block state through editor operations', function (): void {
    $heroBlock = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero']);
    $cardsBlock = Widget::factory()->create(['key' => 'cards', 'name' => 'Cards']);
    $featureBlock = Widget::factory()->create(['key' => 'feature', 'name' => 'Feature']);

    $layoutBuilder = new LayoutBuilderMutationHarness;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'meta' => ['html_class' => 'lead'], 'occurrence' => 1],
                ['widget_key' => 'cards', 'meta' => [], 'occurrence' => 1],
            ],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = [
        'main' => [
            [
                [
                    'asset_id' => 10,
                    'asset_type' => 'Page',
                    'container' => 'main',
                    'occurrence' => 1,
                ],
            ],
            [],
        ],
        'aside' => [],
    ];
    $layoutBuilder->originalAssets = $layoutBuilder->assets;
    $layoutBuilder->selectedRecords = ['main' => [['Page.10'], []], 'aside' => []];
    $layoutBuilder->knownContainerKeys = ['main', 'aside'];
    $layoutBuilder->seedContainerBlocks([
        'main' => [$heroBlock, $cardsBlock],
        'aside' => [],
    ]);

    $layoutBuilder->saveContainer([
        'key' => 'primary',
        'meta' => ['area' => 'content'],
    ], 'main');
    $renamedAssetContainer = $layoutBuilder->assets['primary'][0][0]['container'] ?? null;
    $layoutBuilder->duplicateContainer('primary');
    $addedBlockIndex = $layoutBuilder->addBlockToContainer($featureBlock, 'primary');
    $layoutBuilder->insertContainerBlockAtPositionForTest('primary', $addedBlockIndex, 0);
    $layoutBuilder->normalizeContainerBlockOccurrencesForTest('primary');
    $layoutBuilder->editLayoutBlock('primary', 0, ['html_class' => 'featured']);
    $layoutBuilder->duplicateBlock('primary', 0, withAssets: false);
    $layoutBuilder->removeBlock('primary', 1);
    $layoutBuilder->removeContainer('aside');

    $primaryWidgets = $layoutBuilder->containers['primary']['widgets'];

    expect($layoutBuilder->containers)->toHaveKey('primary')
        ->and($layoutBuilder->containers)->not->toHaveKey('main')
        ->and($layoutBuilder->containers)->not->toHaveKey('aside')
        ->and($renamedAssetContainer)->toBe('primary')
        ->and($layoutBuilder->knownContainerKeys)->toContain('primary')
        ->and($layoutBuilder->knownContainerKeys)->not->toContain('main', 'aside')
        ->and($primaryWidgets[0]['widget_key'])->toBe('feature')
        ->and($primaryWidgets[0]['meta']['html_class'])->toBe('featured')
        ->and($primaryWidgets)->toHaveCount(3)
        ->and($layoutBuilder->containerBlocksForTest()['primary'][0]->is($featureBlock))->toBeTrue()
        ->and($layoutBuilder->containerWidgetKeysForTest())->toContain('feature', 'hero')
        ->and($layoutBuilder->events)->toContain('assertCanUpdateLayout', 'ensureLoaded', 'layoutUpdated')
        ->and($layoutBuilder->layoutModified)->toBeTrue();
});

it('normalizes persisted legacy container definitions when loading layout state', function (): void {
    $layoutBuilder = new LayoutBuilderMutationHarness;
    $layoutBuilder->layout = Layout::factory()->create([
        'containers' => [
            'legacy' => [
                'blocks' => [
                    ['block_key' => 'hero', 'meta' => ['html_class' => 'legacy']],
                    'raw-widget-key',
                ],
                'meta' => ['area' => 'main'],
            ],
        ],
    ]);
    $layoutBuilder->containers = null;

    $layoutBuilder->setupContainersFromLayoutForTest();
    $layoutBuilder->setupContainersFromLayoutForTest();

    expect($layoutBuilder->containers['legacy']['widgets'][0])->toMatchArray([
        'widget_key' => 'hero',
        'meta' => ['html_class' => 'legacy'],
    ])
        ->and($layoutBuilder->containers['legacy']['widgets'][0])->not->toHaveKey('block_key')
        ->and($layoutBuilder->containers['legacy']['widgets'][1])->toBe('raw-widget-key')
        ->and($layoutBuilder->knownContainerKeys)->toBe(['legacy']);
});

/**
 * @param  array<array-key, mixed>  $data
 * @param  array<array-key, mixed>  $arguments
 */
function invokeLayoutBuilderAction(
    Action $action,
    LayoutBuilderActionFlowHarness $layoutBuilder,
    array $data = [],
    array $arguments = [],
): void {
    $closure = $action->getActionFunction();

    expect($closure)->not->toBeNull();

    $action->evaluate(
        $closure,
        [
            'action' => $action,
            'livewire' => $layoutBuilder,
            'data' => $data,
            'arguments' => $arguments,
        ],
        [
            Action::class => $action,
            LayoutBuilder::class => $layoutBuilder,
            LayoutBuilderActionFlowHarness::class => $layoutBuilder,
        ],
    );
}

/**
 * @param  array<array-key, mixed>  $data
 * @param  array<array-key, mixed>  $arguments
 */
function invokeLayoutBuilderBlockAssetEditAction(
    Action $action,
    LayoutBuilderActionFlowHarness $layoutBuilder,
    WidgetAsset $record,
    Schema $schema,
    array $data,
    array $arguments,
): void {
    $closure = $action->getActionFunction();

    expect($closure)->not->toBeNull();

    $action->evaluate(
        $closure,
        [
            'record' => $record,
            'data' => $data,
            'livewire' => $layoutBuilder,
            'arguments' => $arguments,
            'action' => $action,
            'schema' => $schema,
        ],
        [
            Action::class => $action,
            LayoutBuilder::class => $layoutBuilder,
            LayoutBuilderActionFlowHarness::class => $layoutBuilder,
            WidgetAsset::class => $record,
            Schema::class => $schema,
        ],
    );
}

function layoutBuilderActionSchema(LayoutBuilderActionFlowHarness $layoutBuilder): Schema
{
    unset($layoutBuilder);

    return Schema::make(new LayoutBuilderActionSchemaHarness)->operation('edit');
}

/**
 * @param  array<array-key, mixed>  $arguments
 */
function layoutBuilderActionValue(
    Action $action,
    string $property,
    LayoutBuilderActionFlowHarness $layoutBuilder,
    array $arguments = [],
    ?Schema $schema = null,
): mixed {
    $value = layoutBuilderActionRawProperty($action, $property);

    if (! $value instanceof Closure) {
        if ($property !== 'extraAttributes') {
            return $value;
        }

        $attributes = [];

        foreach ($value as $extraAttributes) {
            $attributes = [
                ...$attributes,
                ...(is_array($extraAttributes)
                    ? $extraAttributes
                    : $action->evaluate($extraAttributes, ['arguments' => $arguments, 'livewire' => $layoutBuilder])),
            ];
        }

        return $attributes;
    }

    $schema ??= layoutBuilderActionSchema($layoutBuilder);

    return $action->evaluate(
        $value,
        [
            'action' => $action,
            'arguments' => $arguments,
            'livewire' => $layoutBuilder,
            'schema' => $schema,
        ],
        [
            Action::class => $action,
            LayoutBuilder::class => $layoutBuilder,
            LayoutBuilderActionFlowHarness::class => $layoutBuilder,
            Schema::class => $schema,
        ],
    );
}

function layoutBuilderActionRawProperty(Action $action, string $property): mixed
{
    $reflection = new ReflectionClass($action);

    while ($reflection !== false) {
        if ($reflection->hasProperty($property)) {
            $propertyReflection = $reflection->getProperty($property);

            return $propertyReflection->getValue($action);
        }

        $reflection = $reflection->getParentClass();
    }

    throw new RuntimeException(sprintf('Action property [%s] was not found.', $property));
}

function invokeLayoutBuilderActionFactoryMethod(LayoutBuilderActionFactory $factory, string $method): mixed
{
    $reflection = new ReflectionMethod($factory, $method);

    return $reflection->invoke($factory);
}

final class LayoutBuilderActionSchemaHarness extends Component implements HasSchemas
{
    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function getOldSchemaState(string $statePath): mixed
    {
        unset($statePath);

        return null;
    }

    /**
     * @param  array<SchemaComponent>  $skipComponentsChildContainersWhileSearching
     */
    public function getSchemaComponent(string $key, bool $withHidden = false, array $skipComponentsChildContainersWhileSearching = []): SchemaComponent|Action|ActionGroup|null
    {
        unset($key, $withHidden, $skipComponentsChildContainersWhileSearching);

        return null;
    }

    public function getSchema(string $name): ?Schema
    {
        unset($name);

        return null;
    }

    public function currentlyValidatingSchema(?Schema $schema): void
    {
        unset($schema);
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return null;
    }

    public function getResource(): ?string
    {
        return null;
    }
}
