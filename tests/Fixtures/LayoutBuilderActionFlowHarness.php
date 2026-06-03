<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Override;

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

    public ?int $widgetAssetCountResult = 1;

    public ?Widget $containerWidgetRecord = null;

    public bool $widgetHasPageAssetsResult = false;

    public bool $widgetHasGlobalAssetsResult = true;

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
    public function editLayoutWidget(string $containerKey, int $widgetIndex, array $data): void
    {
        $this->record('editLayoutWidget', [$containerKey, $widgetIndex, $data]);
    }

    /**
     * @param  array<int, int|string>  $widgets
     */
    #[Override]
    public function addWidgetsToContainer(string $containerKey, array $widgets, ?string $actionModalId = null, ?int $position = null): void
    {
        $this->record('addWidgetsToContainer', [$containerKey, $widgets, $actionModalId, $position]);
    }

    #[Override]
    public function duplicateWidget(string $containerKey, int $originalIndex, bool $withAssets = true): void
    {
        $this->record('duplicateWidget', [$containerKey, $originalIndex, $withAssets]);
    }

    #[Override]
    public function moveWidgetUp(string $containerKey, int $widgetIndex): void
    {
        $this->record('moveWidgetUp', [$containerKey, $widgetIndex]);
    }

    #[Override]
    public function moveWidgetDown(string $containerKey, int $widgetIndex): void
    {
        $this->record('moveWidgetDown', [$containerKey, $widgetIndex]);
    }

    #[Override]
    public function moveWidgetToContainer(string $containerKey, int $widgetIndex, string $targetContainerKey): void
    {
        $this->record('moveWidgetToContainer', [$containerKey, $widgetIndex, $targetContainerKey]);
    }

    #[Override]
    public function removeWidget(string $containerKey, int $widgetIndex): void
    {
        $this->record('removeWidget', [$containerKey, $widgetIndex]);
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
    public function getContainerWidgetConfigurator(string $containerKey, int $widgetIndex): string
    {
        $this->record('getContainerWidgetConfigurator', [$containerKey, $widgetIndex]);

        return DefaultWidgetConfigurator::getKey();
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
    public function canMoveWidgetUp(string $containerKey, int $widgetIndex): bool
    {
        $this->record('canMoveWidgetUp', [$containerKey, $widgetIndex]);

        return true;
    }

    #[Override]
    public function canMoveWidgetDown(string $containerKey, int $widgetIndex): bool
    {
        $this->record('canMoveWidgetDown', [$containerKey, $widgetIndex]);

        return true;
    }

    #[Override]
    public function canMoveWidgetToAnotherContainer(string $containerKey, int $widgetIndex): bool
    {
        $this->record('canMoveWidgetToAnotherContainer', [$containerKey, $widgetIndex]);

        return true;
    }

    #[Override]
    public function canMoveAssetUp(string $containerKey, int $widgetIndex, int $assetIndex): bool
    {
        $this->record('canMoveAssetUp', [$containerKey, $widgetIndex, $assetIndex]);

        return true;
    }

    #[Override]
    public function canMoveAssetDown(string $containerKey, int $widgetIndex, int $assetIndex): bool
    {
        $this->record('canMoveAssetDown', [$containerKey, $widgetIndex, $assetIndex]);

        return true;
    }

    #[Override]
    public function countWidgetAssets(string $containerKey, int $widgetIndex): int
    {
        $this->record('countWidgetAssets', [$containerKey, $widgetIndex]);

        return $this->widgetAssetCountResult ?? count($this->assets[$containerKey][$widgetIndex] ?? []);
    }

    #[Override]
    public function hasPageAssets(string $containerKey, int $widgetIndex): bool
    {
        $this->record('hasPageAssets', [$containerKey, $widgetIndex]);

        return $this->hasPageAssetsResult;
    }

    #[Override]
    public function inPageContext(): bool
    {
        return $this->pageContextResult;
    }

    /**
     * @param  array{containerKey: string, widgetIndex: int, hasPageAssets?: bool}  $arguments
     * @param  array<int, int|string>  $assets
     */
    #[Override]
    public function addAssetsToWidget(array $arguments, string $type, array $assets): void
    {
        $this->record('addAssetsToWidget', [$arguments, $type, $assets]);
    }

    #[Override]
    public function moveAssetUp(string $containerKey, int $widgetIndex, int $assetIndex): void
    {
        $this->record('moveAssetUp', [$containerKey, $widgetIndex, $assetIndex]);
    }

    #[Override]
    public function moveAssetDown(string $containerKey, int $widgetIndex, int $assetIndex): void
    {
        $this->record('moveAssetDown', [$containerKey, $widgetIndex, $assetIndex]);
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function getSelectedAssets(string $containerKey, int $widgetIndex): array
    {
        $this->record('getSelectedAssets', [$containerKey, $widgetIndex]);

        return $this->selectedAssetValues;
    }

    #[Override]
    public function removeSelectedAssets(string $containerKey, int $widgetIndex): void
    {
        $this->record('removeSelectedAssets', [$containerKey, $widgetIndex]);
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
    public function getCurrentWidgetAssetWorkspaceId(?Widget $widget = null): int
    {
        unset($widget);

        return 0;
    }

    #[Override]
    public function getContainerWidgetOccurrence(string $containerKey, int $widgetIndex): int
    {
        $this->record('getContainerWidgetOccurrence', [$containerKey, $widgetIndex]);

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
    public function getWidgetAssetsByType(string $containerKey, int $widgetIndex, string $type): array
    {
        $this->record('getWidgetAssetsByType', [$containerKey, $widgetIndex, $type]);

        return parent::getWidgetAssetsByType($containerKey, $widgetIndex, $type);
    }

    #[Override]
    public function widgetHasPageAssets(Widget $widget): bool
    {
        $this->record('widgetHasPageAssets', [$widget->getKey()]);

        return $this->widgetHasPageAssetsResult;
    }

    #[Override]
    public function widgetHasGlobalAssets(Widget $widget): bool
    {
        $this->record('widgetHasGlobalAssets', [$widget->getKey()]);

        return $this->widgetHasGlobalAssetsResult;
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
    public function reloadContainerWidgetAsset(string $containerKey, int $widgetIndex, int $index): void
    {
        $this->record('reloadContainerWidgetAsset', [$containerKey, $widgetIndex, $index]);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    #[Override]
    public function updateWidgetAssetContentState(string $containerKey, int $widgetIndex, int $index, array $data): void
    {
        $this->record('updateWidgetAssetContentState', [$containerKey, $widgetIndex, $index, $data]);

        parent::updateWidgetAssetContentState($containerKey, $widgetIndex, $index, $data);
    }

    #[Override]
    public function togglePageAssets(string $containerKey, int $widgetIndex, mixed $page): void
    {
        $this->record('togglePageAssets', [$containerKey, $widgetIndex, $page]);
    }

    #[Override]
    public function getContainerWidget(string $containerKey, int $widgetIndex): Widget
    {
        $this->record('getContainerWidget', [$containerKey, $widgetIndex]);

        if ($this->containerWidgetRecord instanceof Widget) {
            return $this->containerWidgetRecord;
        }

        $widget = Widget::factory()->create([
            'name' => 'Hero widget',
            'key' => 'hero',
            'admin' => [
                'asset_types' => ['Page'],
            ],
        ]);
        $widget->setRelation('assets', new EloquentCollection);

        $this->containerWidgetRecord = $widget;

        return $widget;
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
