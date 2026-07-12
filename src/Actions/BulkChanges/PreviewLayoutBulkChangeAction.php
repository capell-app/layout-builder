<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LayoutBulkChangeCriteriaData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static LayoutBulkChangeRun run(LayoutBulkChangeCriteriaData $criteria, LayoutBulkWidgetOperationData $operation, ?int $actorId = null)
 */
final class PreviewLayoutBulkChangeAction
{
    use AsAction;

    /** @param array<string, mixed> $containers */
    public static function hashContainers(array $containers): string
    {
        return hash('sha256', json_encode($containers, JSON_THROW_ON_ERROR));
    }

    public function handle(LayoutBulkChangeCriteriaData $criteria, LayoutBulkWidgetOperationData $operation, ?int $actorId = null): LayoutBulkChangeRun
    {
        return DB::transaction(function () use ($criteria, $operation, $actorId): LayoutBulkChangeRun {
            $run = LayoutBulkChangeRun::query()->create([
                'status' => LayoutBulkChangeRunStatus::Previewed,
                'criteria' => $criteria->toPayload(),
                'operation' => $operation->toPayload(),
                'created_by' => $actorId,
            ]);

            $summary = ['target_layouts' => 0, 'target_pages' => 0, 'changed_layouts' => 0, 'blocked_layouts' => 0, 'skipped_layouts' => 0, 'unchanged_layouts' => 0];

            ResolveLayoutBulkChangeTargetsAction::run($criteria)->each(function (Layout $layout) use ($run, $operation, &$summary): void {
                $summary['target_layouts']++;
                $original = is_array($layout->containers) ? $layout->containers : [];
                $operationResult = ApplyLayoutWidgetOperationToContainersAction::run($original, $operation);
                $pageCount = $this->pageCountForLayout($layout);
                $summary['target_pages'] += $pageCount;
                $blockingWarnings = $this->defaultAssetWarnings($operationResult->assetMoves);
                $warnings = [
                    ...$operationResult->warnings,
                    ...$blockingWarnings,
                    ...$this->removedAssetWarnings($operation, $operationResult->assetRemovals),
                ];
                $status = $operationResult->changed ? LayoutBulkChangeResultStatus::Changed : LayoutBulkChangeResultStatus::Skipped;

                if ($operationResult->changed) {
                    $summary['changed_layouts']++;
                } else {
                    $summary['skipped_layouts']++;
                }

                if ($blockingWarnings !== []) {
                    $status = LayoutBulkChangeResultStatus::Blocked;
                    $summary['blocked_layouts']++;
                    $summary['changed_layouts'] = max(0, $summary['changed_layouts'] - 1);
                }

                LayoutBulkChangeResult::query()->create([
                    'run_id' => $run->id,
                    'layout_id' => $layout->id,
                    'page_count' => $pageCount,
                    'status' => $status,
                    'original_container_hash' => self::hashContainers($original),
                    'proposed_container_hash' => self::hashContainers($operationResult->containers),
                    'original_containers' => $original,
                    'proposed_containers' => $operationResult->containers,
                    'changes' => [
                        'messages' => $operationResult->changes,
                        'asset_moves' => $operationResult->assetMoves,
                        'asset_removals' => $operationResult->assetRemovals,
                        'container_diffs' => $operationResult->containerDiffs,
                    ],
                    'warnings' => $warnings,
                    'skipped_reason' => $operationResult->skippedReason,
                ]);
            });

            if ($summary['blocked_layouts'] > 0) {
                $run->status = LayoutBulkChangeRunStatus::Blocked;
            }

            $run->summary = $summary;
            $run->save();

            return $run->refresh();
        });
    }

    private function pageCountForLayout(Layout $layout): int
    {
        $count = 0;

        foreach (CapellCore::getPageVariationModels() as $pageModel) {
            if (is_a($pageModel, Model::class, true)) {
                $count += $pageModel::query()->where('layout_id', $layout->id)->count();
            }
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $assetMoves
     * @return list<string>
     */
    private function defaultAssetWarnings(array $assetMoves): array
    {
        $warnings = [];

        foreach ($assetMoves as $assetMove) {
            if ($this->integerValue($assetMove['from_occurrence'] ?? null, 1) === $this->integerValue($assetMove['to_occurrence'] ?? null, 1)) {
                continue;
            }

            $widgetKey = $this->stringValue($assetMove['widget_key'] ?? null);
            $widget = Widget::query()->where('key', $widgetKey)->first();

            if (! $widget instanceof Widget) {
                continue;
            }

            $fromOccurrence = $this->integerValue($assetMove['from_occurrence'] ?? null, 1);

            if (WidgetAsset::query()->where('widget_id', $widget->id)->where('occurrence', $fromOccurrence)->whereNull('pageable_type')->whereNull('pageable_id')->exists()) {
                $warnings[] = sprintf('Default assets for widget [%s] occurrence [%d] would become ambiguous after this move.', $widgetKey, $fromOccurrence);
            }
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param  list<array<string, mixed>>  $assetRemovals
     * @return list<string>
     */
    private function removedAssetWarnings(LayoutBulkWidgetOperationData $operation, array $assetRemovals): array
    {
        if ($operation->typeEnum() !== LayoutBulkWidgetOperationType::RemoveWidget || $operation->removeWidgetAssetMode !== 'warn') {
            return [];
        }

        $assetCount = $this->pageScopedAssetCountForRemovals($assetRemovals);

        if ($assetCount === 0) {
            return [];
        }

        return [sprintf('Removing this widget will leave %d page-scoped widget asset%s unused. Select auto-delete page-scoped assets to remove them during apply.', $assetCount, $assetCount === 1 ? '' : 's')];
    }

    /**
     * @param  list<array<string, mixed>>  $assetRemovals
     */
    private function pageScopedAssetCountForRemovals(array $assetRemovals): int
    {
        $count = 0;

        foreach ($assetRemovals as $assetRemoval) {
            $widget = Widget::query()->where('key', $this->stringValue($assetRemoval['widget_key'] ?? null))->first();

            if (! $widget instanceof Widget) {
                continue;
            }

            $count += WidgetAsset::query()
                ->where('widget_id', $widget->id)
                ->where('container', $this->stringValue($assetRemoval['container'] ?? null))
                ->where('occurrence', $this->integerValue($assetRemoval['occurrence'] ?? null, 1))
                ->whereNotNull('pageable_type')
                ->whereNotNull('pageable_id')
                ->count();
        }

        return $count;
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
