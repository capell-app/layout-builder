<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LayoutBulkChangeCriteriaData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

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
                $warnings = [...$operationResult->warnings, ...$this->defaultAssetWarnings($operationResult->assetMoves)];
                $status = $operationResult->changed ? LayoutBulkChangeResultStatus::Changed : LayoutBulkChangeResultStatus::Skipped;

                if ($operationResult->changed) {
                    $summary['changed_layouts']++;
                } else {
                    $summary['skipped_layouts']++;
                }

                if ($warnings !== []) {
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
                    'changes' => ['messages' => $operationResult->changes, 'asset_moves' => $operationResult->assetMoves],
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

    /** @param list<array<string, mixed>> $assetMoves */
    private function defaultAssetWarnings(array $assetMoves): array
    {
        $warnings = [];

        foreach ($assetMoves as $assetMove) {
            if ((int) ($assetMove['from_occurrence'] ?? 1) === (int) ($assetMove['to_occurrence'] ?? 1)) {
                continue;
            }

            $widget = Widget::query()->where('key', (string) ($assetMove['widget_key'] ?? ''))->first();

            if (! $widget instanceof Widget) {
                continue;
            }

            if (WidgetAsset::query()->where('widget_id', $widget->id)->where('occurrence', (int) $assetMove['from_occurrence'])->whereNull('pageable_type')->whereNull('pageable_id')->exists()) {
                $warnings[] = sprintf('Default assets for widget [%s] occurrence [%d] would become ambiguous after this move.', (string) $assetMove['widget_key'], (int) $assetMove['from_occurrence']);
            }
        }

        return array_values(array_unique($warnings));
    }
}
