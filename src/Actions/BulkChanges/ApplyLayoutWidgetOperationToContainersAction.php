<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationResultData;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static LayoutBulkWidgetOperationResultData run(array<string, mixed> $containers, LayoutBulkWidgetOperationData $operation)
 */
final class ApplyLayoutWidgetOperationToContainersAction
{
    use AsAction;

    private const string MOVE_ID = '_bulk_change_move_id';

    /** @param array<string, mixed> $containers */
    public function handle(array $containers, LayoutBulkWidgetOperationData $operation): LayoutBulkWidgetOperationResultData
    {
        $original = $this->normaliseContainers($containers);
        $working = $original;
        $assetMoves = $this->emptyArrayList();
        $assetRemovals = $this->emptyArrayList();
        $changes = $this->emptyStringList();

        $result = match ($operation->typeEnum()) {
            LayoutBulkWidgetOperationType::MoveWidget => $this->moveWidget($working, $operation, $assetMoves, $changes),
            LayoutBulkWidgetOperationType::RemoveWidget => $this->removeWidget($working, $operation, $assetRemovals, $changes),
            LayoutBulkWidgetOperationType::SwapWidgets => $this->swapWidgets($working, $operation, $assetMoves, $changes),
            LayoutBulkWidgetOperationType::MoveWidgetToContainer => $this->moveWidgetToContainer($working, $operation, $assetMoves, $changes),
        };

        if ($result instanceof LayoutBulkWidgetOperationResultData) {
            return $result;
        }

        $working = $this->stripInternalMoveIds($this->renumberOccurrences($working, $assetMoves));

        if ($working === $original) {
            return new LayoutBulkWidgetOperationResultData($working, skippedReason: 'The operation did not change this layout.');
        }

        return new LayoutBulkWidgetOperationResultData(
            containers: $working,
            changed: true,
            changes: $changes,
            assetMoves: $assetMoves,
            assetRemovals: $assetRemovals,
            containerDiffs: $this->containerDiffs($original, $working),
        );
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<string, array<string, mixed>>
     */
    private function normaliseContainers(array $containers): array
    {
        $normalised = [];
        $seen = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $widgets = $this->emptyArrayList();
            $configuredWidgets = $container['widgets'] ?? [];

            if (! is_iterable($configuredWidgets)) {
                $configuredWidgets = [];
            }

            foreach ($configuredWidgets as $widget) {
                if (is_string($widget) && $widget !== '') {
                    $widget = ['widget_key' => $widget];
                }

                if (! is_array($widget)) {
                    continue;
                }

                if (! is_string($widget['widget_key'] ?? null)) {
                    continue;
                }

                if ($widget['widget_key'] === '') {
                    continue;
                }

                $widgetKey = $widget['widget_key'];
                $seen[$widgetKey] = ($seen[$widgetKey] ?? 0) + 1;
                $widget['container'] = is_string($widget['container'] ?? null) ? $widget['container'] : (string) $containerKey;
                $widget['occurrence'] = is_numeric($widget['occurrence'] ?? null) ? (int) $widget['occurrence'] : $seen[$widgetKey];
                $widgets[] = $widget;
            }

            $container['widgets'] = $widgets;
            $normalised[(string) $containerKey] = $container;
        }

        return $normalised;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array<string, mixed>>  $assetMoves
     * @param  list<string>  $changes
     */
    private function moveWidget(array &$containers, LayoutBulkWidgetOperationData $operation, array &$assetMoves, array &$changes): ?LayoutBulkWidgetOperationResultData
    {
        $source = $this->positionsForWidget($containers, $operation);
        $target = $this->firstPositionForWidget($containers, $operation->targetWidgetKey);

        if ($source === []) {
            return $this->skipped($containers, sprintf('Source widget [%s] was not found.', $operation->sourceWidgetKey));
        }

        if ($target === null) {
            return $this->skipped($containers, sprintf('Target widget [%s] was not found.', (string) $operation->targetWidgetKey));
        }

        $this->markMoved($containers, $source);
        $moved = $this->removePositions($containers, $source);
        $target = $this->firstPositionForWidget($containers, $operation->targetWidgetKey);

        if ($target === null) {
            return $this->skipped($containers, sprintf('Target widget [%s] was removed by the operation.', (string) $operation->targetWidgetKey));
        }

        $this->insertWidgets($containers, $target['container'], $target['index'] + ($operation->placement === 'before' ? 0 : 1), $moved);
        $this->captureAssetMoves($moved, $target['container'], $assetMoves);
        $changes[] = sprintf('Moved widget [%s] %s widget [%s].', $operation->sourceWidgetKey, $operation->placement, (string) $operation->targetWidgetKey);

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array<string, mixed>>  $assetRemovals
     * @param  list<string>  $changes
     */
    private function removeWidget(array &$containers, LayoutBulkWidgetOperationData $operation, array &$assetRemovals, array &$changes): ?LayoutBulkWidgetOperationResultData
    {
        $source = $this->positionsForWidget($containers, $operation);

        if ($source === []) {
            return $this->skipped($containers, sprintf('Source widget [%s] was not found.', $operation->sourceWidgetKey));
        }

        $removed = $this->removePositions($containers, $source);
        $this->captureAssetRemovals($removed, $assetRemovals);
        $changes[] = sprintf('Removed widget [%s].', $operation->sourceWidgetKey);

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array<string, mixed>>  $assetMoves
     * @param  list<string>  $changes
     */
    private function swapWidgets(array &$containers, LayoutBulkWidgetOperationData $operation, array &$assetMoves, array &$changes): ?LayoutBulkWidgetOperationResultData
    {
        $source = $this->firstPositionForWidget($containers, $operation->sourceWidgetKey, $operation->sourceContainerKey);
        $target = $this->firstPositionForWidget($containers, $operation->targetWidgetKey);

        if ($source === null) {
            return $this->skipped($containers, sprintf('Source widget [%s] was not found.', $operation->sourceWidgetKey));
        }

        if ($target === null) {
            return $this->skipped($containers, sprintf('Target widget [%s] was not found.', (string) $operation->targetWidgetKey));
        }

        $sourceWidget = $this->widgetAt($containers, $source['container'], $source['index']);
        $targetWidget = $this->widgetAt($containers, $target['container'], $target['index']);

        if ($sourceWidget === null || $targetWidget === null) {
            return $this->skipped($containers, 'The source or target widget was removed by the operation.');
        }

        $sourceWidget[self::MOVE_ID] = bin2hex(random_bytes(6));
        $targetWidget[self::MOVE_ID] = bin2hex(random_bytes(6));
        $this->setWidgetAt($containers, $source['container'], $source['index'], $targetWidget);
        $this->setWidgetAt($containers, $target['container'], $target['index'], $sourceWidget);
        $this->captureAssetMoves([$sourceWidget], $target['container'], $assetMoves);
        $this->captureAssetMoves([$targetWidget], $source['container'], $assetMoves);
        $changes[] = sprintf('Swapped widgets [%s] and [%s].', $operation->sourceWidgetKey, (string) $operation->targetWidgetKey);

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array<string, mixed>>  $assetMoves
     * @param  list<string>  $changes
     */
    private function moveWidgetToContainer(array &$containers, LayoutBulkWidgetOperationData $operation, array &$assetMoves, array &$changes): ?LayoutBulkWidgetOperationResultData
    {
        $targetContainer = $operation->targetContainerKey;

        if ($targetContainer === null || ! array_key_exists($targetContainer, $containers)) {
            return $this->skipped($containers, sprintf('Target container [%s] was not found.', (string) $targetContainer));
        }

        $source = $this->positionsForWidget($containers, $operation);

        if ($source === []) {
            return $this->skipped($containers, sprintf('Source widget [%s] was not found.', $operation->sourceWidgetKey));
        }

        $this->markMoved($containers, $source);
        $moved = $this->removePositions($containers, $source);
        $insertIndex = $this->containerInsertIndex($containers, $targetContainer, $operation);

        if ($insertIndex === null) {
            return $this->skipped($containers, sprintf('Target widget [%s] was not found in container [%s].', (string) $operation->targetWidgetKey, $targetContainer));
        }

        $this->insertWidgets($containers, $targetContainer, $insertIndex, $moved);
        $this->captureAssetMoves($moved, $targetContainer, $assetMoves);
        $changes[] = sprintf('Moved widget [%s] to container [%s].', $operation->sourceWidgetKey, $targetContainer);

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     */
    private function containerInsertIndex(array $containers, string $targetContainer, LayoutBulkWidgetOperationData $operation): ?int
    {
        if ($operation->placement === 'top') {
            return 0;
        }

        if ($operation->placement === 'bottom') {
            return count($this->widgets($containers[$targetContainer] ?? []));
        }

        $target = $this->firstPositionForWidget($containers, $operation->targetWidgetKey, $targetContainer);

        return $target === null ? null : $target['index'] + ($operation->placement === 'before' ? 0 : 1);
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array{container: string, index: int}>  $positions
     */
    private function markMoved(array &$containers, array $positions): void
    {
        foreach ($positions as $position) {
            $widget = $this->widgetAt($containers, $position['container'], $position['index']);

            if ($widget === null) {
                continue;
            }

            $widget[self::MOVE_ID] = bin2hex(random_bytes(6));
            $this->setWidgetAt($containers, $position['container'], $position['index'], $widget);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array{container: string, index: int}>  $positions
     * @return list<array<string, mixed>>
     */
    private function removePositions(array &$containers, array $positions): array
    {
        usort($positions, fn (array $first, array $second): int => [$second['container'], $second['index']] <=> [$first['container'], $first['index']]);
        $removed = [];

        foreach ($positions as $position) {
            $widget = $this->widgetAt($containers, $position['container'], $position['index']);

            if ($widget !== null) {
                array_unshift($removed, $widget);
                $this->removeWidgetAt($containers, $position['container'], $position['index']);
            }
        }

        return $removed;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return list<array{container: string, index: int}>
     */
    private function positionsForWidget(array $containers, LayoutBulkWidgetOperationData|string $operation, ?string $containerKey = null, string $occurrenceMode = 'all'): array
    {
        $widgetKey = $operation instanceof LayoutBulkWidgetOperationData ? $operation->sourceWidgetKey : $operation;
        $containerKey = $operation instanceof LayoutBulkWidgetOperationData ? $operation->sourceContainerKey : $containerKey;
        $occurrenceMode = $operation instanceof LayoutBulkWidgetOperationData ? $operation->occurrenceMode : $occurrenceMode;
        $specificOccurrence = $operation instanceof LayoutBulkWidgetOperationData ? $operation->sourceOccurrenceNumber : null;
        $positions = [];

        foreach ($containers as $currentContainerKey => $container) {
            if ($containerKey !== null && $currentContainerKey !== $containerKey) {
                continue;
            }

            foreach ($this->widgets($container) as $index => $widget) {
                if (! is_array($widget)) {
                    continue;
                }

                if (($widget['widget_key'] ?? null) !== $widgetKey) {
                    continue;
                }

                if ($occurrenceMode === 'specific' && $this->integerValue($widget['occurrence'] ?? null, 0) !== $specificOccurrence) {
                    continue;
                }

                $positions[] = ['container' => $currentContainerKey, 'index' => (int) $index];

                if ($occurrenceMode === 'first' || $occurrenceMode === 'specific') {
                    return $positions;
                }
            }
        }

        return $positions;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return array{container: string, index: int}|null
     */
    private function firstPositionForWidget(array $containers, ?string $widgetKey, ?string $containerKey = null): ?array
    {
        if ($widgetKey === null || $widgetKey === '') {
            return null;
        }

        return $this->positionsForWidget($containers, $widgetKey, $containerKey, 'first')[0] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $movedWidgets
     * @param  list<array<string, mixed>>  $assetMoves
     */
    private function captureAssetMoves(array $movedWidgets, string $targetContainer, array &$assetMoves): void
    {
        foreach ($movedWidgets as $widget) {
            $assetMoves[] = [
                'widget_key' => $this->stringValue($widget['widget_key'] ?? null),
                'from_container' => $widget['container'] ?? null,
                'from_occurrence' => $this->integerValue($widget['occurrence'] ?? null, 1),
                'to_container' => $targetContainer,
                'to_occurrence' => 0,
            ];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $removedWidgets
     * @param  list<array<string, mixed>>  $assetRemovals
     */
    private function captureAssetRemovals(array $removedWidgets, array &$assetRemovals): void
    {
        foreach ($removedWidgets as $widget) {
            $assetRemovals[] = [
                'widget_key' => $this->stringValue($widget['widget_key'] ?? null),
                'container' => $widget['container'] ?? null,
                'occurrence' => $this->integerValue($widget['occurrence'] ?? null, 1),
            ];
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $original
     * @param  array<string, array<string, mixed>>  $proposed
     * @return list<array<string, mixed>>
     */
    private function containerDiffs(array $original, array $proposed): array
    {
        $diffs = [];
        $containerKeys = array_values(array_unique([...array_keys($original), ...array_keys($proposed)]));

        foreach ($containerKeys as $containerKey) {
            $before = $this->widgetOrder($this->widgets($original[$containerKey] ?? []));
            $after = $this->widgetOrder($this->widgets($proposed[$containerKey] ?? []));

            if ($before === $after) {
                continue;
            }

            $diffs[] = [
                'container' => (string) $containerKey,
                'before' => $before,
                'after' => $after,
            ];
        }

        return $diffs;
    }

    /**
     * @param  list<array<string, mixed>>  $widgets
     * @return list<string>
     */
    private function widgetOrder(array $widgets): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $widget): ?string => is_array($widget) && is_string($widget['widget_key'] ?? null)
                ? sprintf('%s#%d', $widget['widget_key'], $this->integerValue($widget['occurrence'] ?? null, 1))
                : null,
            $widgets,
        )));
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array<string, mixed>>  $assetMoves
     * @return array<string, array<string, mixed>>
     */
    private function renumberOccurrences(array $containers, array &$assetMoves): array
    {
        $seen = [];

        foreach ($containers as $containerKey => $container) {
            foreach ($this->widgets($container) as $index => $widget) {
                if (! is_array($widget)) {
                    continue;
                }

                if (! is_string($widget['widget_key'] ?? null)) {
                    continue;
                }

                $widgetKey = $widget['widget_key'];
                $seen[$widgetKey] = ($seen[$widgetKey] ?? 0) + 1;
                $occurrence = is_numeric($widget['occurrence'] ?? null) ? (int) $widget['occurrence'] : $seen[$widgetKey];

                if ($this->occurrenceCollisionExists($containers, $widgetKey, $occurrence, (string) $containerKey, (int) $index)) {
                    $occurrence = $seen[$widgetKey];
                }

                $widget['container'] = (string) $containerKey;
                $widget['occurrence'] = $occurrence;
                $this->setWidgetAt($containers, (string) $containerKey, (int) $index, $widget);

                if (is_string($widget[self::MOVE_ID] ?? null)) {
                    foreach ($assetMoves as $moveIndex => $assetMove) {
                        if (($assetMove['widget_key'] ?? null) === $widgetKey && $this->integerValue($assetMove['to_occurrence'] ?? null, 0) === 0) {
                            $assetMoves[$moveIndex]['to_container'] = (string) $containerKey;
                            $assetMoves[$moveIndex]['to_occurrence'] = $occurrence;
                            break;
                        }
                    }
                }
            }
        }

        return $containers;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     */
    private function occurrenceCollisionExists(array $containers, string $widgetKey, int $occurrence, string $currentContainer, int $currentIndex): bool
    {
        foreach ($containers as $containerKey => $container) {
            foreach ($this->widgets($container) as $index => $widget) {
                if ($containerKey === $currentContainer && (int) $index === $currentIndex) {
                    continue;
                }

                if (is_array($widget) && ($widget['widget_key'] ?? null) === $widgetKey && is_numeric($widget['occurrence'] ?? null) && (int) $widget['occurrence'] === $occurrence) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return array<string, array<string, mixed>>
     */
    private function stripInternalMoveIds(array $containers): array
    {
        foreach ($containers as $containerKey => $container) {
            foreach ($this->widgets($container) as $index => $widget) {
                unset($widget[self::MOVE_ID]);
                $this->setWidgetAt($containers, (string) $containerKey, (int) $index, $widget);
            }
        }

        return $containers;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     */
    private function skipped(array $containers, string $reason): LayoutBulkWidgetOperationResultData
    {
        return new LayoutBulkWidgetOperationResultData($this->stripInternalMoveIds($containers), skippedReason: $reason);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function emptyArrayList(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    private function emptyStringList(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $container
     * @return list<array<string, mixed>>
     */
    private function widgets(array $container): array
    {
        $widgets = $container['widgets'] ?? [];

        if (! is_array($widgets)) {
            return [];
        }

        $normalised = [];

        foreach ($widgets as $widget) {
            if (is_array($widget)) {
                $normalised[] = $widget;
            }
        }

        return $normalised;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return array<string, mixed>|null
     */
    private function widgetAt(array $containers, string $containerKey, int $index): ?array
    {
        return $this->widgets($containers[$containerKey] ?? [])[$index] ?? null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, mixed>  $widget
     */
    private function setWidgetAt(array &$containers, string $containerKey, int $index, array $widget): void
    {
        $widgets = $this->widgets($containers[$containerKey] ?? []);
        $widgets[$index] = $widget;
        $containers[$containerKey]['widgets'] = array_values($widgets);
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  list<array<string, mixed>>  $widgetsToInsert
     */
    private function insertWidgets(array &$containers, string $containerKey, int $index, array $widgetsToInsert): void
    {
        $widgets = $this->widgets($containers[$containerKey] ?? []);
        array_splice($widgets, $index, 0, $widgetsToInsert);
        $containers[$containerKey]['widgets'] = $widgets;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     */
    private function removeWidgetAt(array &$containers, string $containerKey, int $index): void
    {
        $widgets = $this->widgets($containers[$containerKey] ?? []);
        array_splice($widgets, $index, 1);
        $containers[$containerKey]['widgets'] = $widgets;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }

    private function integerValue(mixed $value, int $fallback): int
    {
        return is_numeric($value) ? (int) $value : $fallback;
    }
}
