<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationResultData;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Lorisleiva\Actions\Concerns\AsAction;

final class ApplyLayoutWidgetOperationToContainersAction
{
    use AsAction;

    private const string MOVE_ID = '_bulk_change_move_id';

    /** @param array<string, mixed> $containers */
    public function handle(array $containers, LayoutBulkWidgetOperationData $operation): LayoutBulkWidgetOperationResultData
    {
        $original = $this->normaliseContainers($containers);
        $working = $original;
        $assetMoves = [];
        $assetRemovals = [];
        $changes = [];

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

    /** @param array<string, mixed> $containers */
    private function normaliseContainers(array $containers): array
    {
        $normalised = [];
        $seen = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $widgets = [];
            foreach (($container['widgets'] ?? []) as $widget) {
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

            $container['widgets'] = array_values($widgets);
            $normalised[(string) $containerKey] = $container;
        }

        return $normalised;
    }

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

        array_splice($containers[$target['container']]['widgets'], $target['index'] + ($operation->placement === 'before' ? 0 : 1), 0, $moved);
        $this->captureAssetMoves($moved, $target['container'], $assetMoves);
        $changes[] = sprintf('Moved widget [%s] %s widget [%s].', $operation->sourceWidgetKey, $operation->placement, (string) $operation->targetWidgetKey);

        return null;
    }

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

        $sourceWidget = $containers[$source['container']]['widgets'][$source['index']];
        $targetWidget = $containers[$target['container']]['widgets'][$target['index']];
        $sourceWidget[self::MOVE_ID] = bin2hex(random_bytes(6));
        $targetWidget[self::MOVE_ID] = bin2hex(random_bytes(6));
        $containers[$source['container']]['widgets'][$source['index']] = $targetWidget;
        $containers[$target['container']]['widgets'][$target['index']] = $sourceWidget;
        $this->captureAssetMoves([$sourceWidget], $target['container'], $assetMoves);
        $this->captureAssetMoves([$targetWidget], $source['container'], $assetMoves);
        $changes[] = sprintf('Swapped widgets [%s] and [%s].', $operation->sourceWidgetKey, (string) $operation->targetWidgetKey);

        return null;
    }

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

        array_splice($containers[$targetContainer]['widgets'], $insertIndex, 0, $moved);
        $this->captureAssetMoves($moved, $targetContainer, $assetMoves);
        $changes[] = sprintf('Moved widget [%s] to container [%s].', $operation->sourceWidgetKey, $targetContainer);

        return null;
    }

    private function containerInsertIndex(array $containers, string $targetContainer, LayoutBulkWidgetOperationData $operation): ?int
    {
        if ($operation->placement === 'top') {
            return 0;
        }

        if ($operation->placement === 'bottom') {
            return count($containers[$targetContainer]['widgets'] ?? []);
        }

        $target = $this->firstPositionForWidget($containers, $operation->targetWidgetKey, $targetContainer);

        return $target === null ? null : $target['index'] + ($operation->placement === 'before' ? 0 : 1);
    }

    private function markMoved(array &$containers, array $positions): void
    {
        foreach ($positions as $position) {
            $containers[$position['container']]['widgets'][$position['index']][self::MOVE_ID] = bin2hex(random_bytes(6));
        }
    }

    private function removePositions(array &$containers, array $positions): array
    {
        usort($positions, fn (array $first, array $second): int => [$second['container'], $second['index']] <=> [$first['container'], $first['index']]);
        $removed = [];

        foreach ($positions as $position) {
            $widget = $containers[$position['container']]['widgets'][$position['index']] ?? null;

            if (is_array($widget)) {
                array_unshift($removed, $widget);
                array_splice($containers[$position['container']]['widgets'], $position['index'], 1);
            }
        }

        return $removed;
    }

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

            foreach (($container['widgets'] ?? []) as $index => $widget) {
                if (! is_array($widget)) {
                    continue;
                }
                if (($widget['widget_key'] ?? null) !== $widgetKey) {
                    continue;
                }
                if ($occurrenceMode === 'specific' && (int) ($widget['occurrence'] ?? 0) !== $specificOccurrence) {
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

    private function firstPositionForWidget(array $containers, ?string $widgetKey, ?string $containerKey = null): ?array
    {
        if ($widgetKey === null || $widgetKey === '') {
            return null;
        }

        return $this->positionsForWidget($containers, $widgetKey, $containerKey, 'first')[0] ?? null;
    }

    private function captureAssetMoves(array $movedWidgets, string $targetContainer, array &$assetMoves): void
    {
        foreach ($movedWidgets as $widget) {
            $assetMoves[] = [
                'widget_key' => (string) $widget['widget_key'],
                'from_container' => $widget['container'] ?? null,
                'from_occurrence' => (int) ($widget['occurrence'] ?? 1),
                'to_container' => $targetContainer,
                'to_occurrence' => 0,
            ];
        }
    }

    private function captureAssetRemovals(array $removedWidgets, array &$assetRemovals): void
    {
        foreach ($removedWidgets as $widget) {
            $assetRemovals[] = [
                'widget_key' => (string) $widget['widget_key'],
                'container' => $widget['container'] ?? null,
                'occurrence' => (int) ($widget['occurrence'] ?? 1),
            ];
        }
    }

    private function containerDiffs(array $original, array $proposed): array
    {
        $diffs = [];
        $containerKeys = array_values(array_unique([...array_keys($original), ...array_keys($proposed)]));

        foreach ($containerKeys as $containerKey) {
            $before = $this->widgetOrder($original[$containerKey]['widgets'] ?? []);
            $after = $this->widgetOrder($proposed[$containerKey]['widgets'] ?? []);

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

    private function widgetOrder(array $widgets): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $widget): ?string => is_array($widget) && is_string($widget['widget_key'] ?? null)
                ? sprintf('%s#%d', $widget['widget_key'], (int) ($widget['occurrence'] ?? 1))
                : null,
            $widgets,
        )));
    }

    private function renumberOccurrences(array $containers, array &$assetMoves): array
    {
        $seen = [];

        foreach ($containers as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $index => $widget) {
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

                $containers[$containerKey]['widgets'][$index]['container'] = (string) $containerKey;
                $containers[$containerKey]['widgets'][$index]['occurrence'] = $occurrence;

                if (is_string($widget[self::MOVE_ID] ?? null)) {
                    foreach ($assetMoves as $moveIndex => $assetMove) {
                        if ($assetMove['widget_key'] === $widgetKey && (int) $assetMove['to_occurrence'] === 0) {
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

    private function occurrenceCollisionExists(array $containers, string $widgetKey, int $occurrence, string $currentContainer, int $currentIndex): bool
    {
        foreach ($containers as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $index => $widget) {
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

    private function stripInternalMoveIds(array $containers): array
    {
        foreach ($containers as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $index => $widget) {
                if (is_array($widget)) {
                    unset($widget[self::MOVE_ID]);
                    $containers[$containerKey]['widgets'][$index] = $widget;
                }
            }
        }

        return $containers;
    }

    private function skipped(array $containers, string $reason): LayoutBulkWidgetOperationResultData
    {
        return new LayoutBulkWidgetOperationResultData($this->stripInternalMoveIds($containers), skippedReason: $reason);
    }
}
