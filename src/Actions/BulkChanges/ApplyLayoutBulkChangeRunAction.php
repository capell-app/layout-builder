<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array<string, mixed> run(LayoutBulkChangeRun $run, ?int $actorId = null)
 */
final class ApplyLayoutBulkChangeRunAction
{
    use AsFake;
    use AsObject;

    /** @return array<string, mixed> */
    public function handle(LayoutBulkChangeRun $run, ?int $actorId = null): array
    {
        throw_if($run->status === LayoutBulkChangeRunStatus::Blocked, LogicException::class, 'This bulk layout change is blocked by preview warnings and cannot be applied.');

        if (in_array($run->status, [LayoutBulkChangeRunStatus::Applied, LayoutBulkChangeRunStatus::PartiallyApplied], true)) {
            return $run->summary ?? [];
        }

        return DB::transaction(function () use ($run, $actorId): array {
            if ($run->status === LayoutBulkChangeRunStatus::Queued) {
                $run->forceFill(['status' => LayoutBulkChangeRunStatus::Applying])->save();
            }

            $applied = 0;
            $drifted = 0;
            $skipped = 0;
            $operation = LayoutBulkWidgetOperationData::fromPayload($run->operation ?? []);

            $results = LayoutBulkChangeResult::query()->where('run_id', $run->id)->where('status', LayoutBulkChangeResultStatus::Changed)->lockForUpdate()->get();

            foreach ($results as $result) {
                $layout = $result->layout_id === null
                    ? null
                    : ScopeLayoutBulkChangeQueryForActorAction::run(Layout::query()->whereKey($result->layout_id), $actorId)
                        ->lockForUpdate()
                        ->first();

                if (! $layout instanceof Layout) {
                    $skipped++;
                    $result->update(['status' => LayoutBulkChangeResultStatus::Skipped, 'skipped_reason' => 'The layout is unavailable or outside the current actor scope.']);

                    continue;
                }

                $current = is_array($layout->containers) ? $layout->containers : [];

                if (PreviewLayoutBulkChangeAction::hashContainers($current) !== $result->original_container_hash) {
                    $drifted++;
                    $result->update(['status' => LayoutBulkChangeResultStatus::Drifted, 'skipped_reason' => 'The layout changed after this preview was generated.']);

                    continue;
                }

                $layout->containers = $result->proposed_containers ?? [];
                $layout->save();
                $this->migratePageScopedAssets($layout, $result);
                $this->deleteRemovedPageScopedAssets($operation, $result);
                $applied++;
                $result->update(['status' => LayoutBulkChangeResultStatus::Applied, 'applied_at' => Date::now()]);
            }

            $summary = [...($run->summary ?? []), 'applied_layouts' => $applied, 'drifted_layouts' => $drifted, 'apply_skipped_layouts' => $skipped];

            $run->forceFill([
                'status' => ($drifted > 0 || $skipped > 0) ? LayoutBulkChangeRunStatus::PartiallyApplied : LayoutBulkChangeRunStatus::Applied,
                'summary' => $summary,
                'approved_by' => $actorId,
                'applied_by' => $actorId,
                'approved_at' => Date::now(),
                'applied_at' => Date::now(),
            ])->save();

            return $summary;
        });
    }

    private function migratePageScopedAssets(Layout $layout, LayoutBulkChangeResult $result): void
    {
        $changes = $result->changes ?? [];

        foreach ($this->arrayList($changes['asset_moves'] ?? []) as $assetMove) {
            $widget = Widget::query()->where('key', $this->stringValue($assetMove['widget_key'] ?? null))->first();

            if (! $widget instanceof Widget) {
                continue;
            }

            foreach ($this->pageScopesForLayout($layout) as $pageScope) {
                WidgetAsset::query()
                    ->where('widget_id', $widget->id)
                    ->where('container', $this->stringValue($assetMove['from_container'] ?? null))
                    ->where('occurrence', $this->integerValue($assetMove['from_occurrence'] ?? null, 1))
                    ->where('pageable_type', $pageScope['type'])
                    ->where('pageable_id', $pageScope['id'])
                    ->update([
                        'container' => $this->stringValue($assetMove['to_container'] ?? null),
                        'occurrence' => $this->integerValue($assetMove['to_occurrence'] ?? null, 1),
                    ]);
            }
        }
    }

    private function deleteRemovedPageScopedAssets(LayoutBulkWidgetOperationData $operation, LayoutBulkChangeResult $result): void
    {
        if ($operation->typeEnum() !== LayoutBulkWidgetOperationType::RemoveWidget || $operation->removeWidgetAssetMode !== 'delete_page_scoped') {
            return;
        }

        $changes = $result->changes ?? [];

        foreach ($this->arrayList($changes['asset_removals'] ?? []) as $assetRemoval) {
            $widget = Widget::query()->where('key', $this->stringValue($assetRemoval['widget_key'] ?? null))->first();

            if (! $widget instanceof Widget) {
                continue;
            }

            WidgetAsset::query()
                ->where('widget_id', $widget->id)
                ->where('container', $this->stringValue($assetRemoval['container'] ?? null))
                ->where('occurrence', $this->integerValue($assetRemoval['occurrence'] ?? null, 1))
                ->whereNotNull('pageable_type')
                ->whereNotNull('pageable_id')
                ->delete();
        }
    }

    /** @return list<array{type: string, id: int|string}> */
    private function pageScopesForLayout(Layout $layout): array
    {
        $scopes = [];

        foreach (CapellCore::getPageVariationModels() as $pageModel) {
            if (! is_a($pageModel, Model::class, true)) {
                continue;
            }

            $pageModel::query()->where('layout_id', $layout->id)->get(['id'])->each(function (Model $page) use (&$scopes): void {
                $pageKey = $page->getKey();

                if (is_int($pageKey) || is_string($pageKey)) {
                    $scopes[] = ['type' => $page->getMorphClass(), 'id' => $pageKey];
                }
            });
        }

        return $scopes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function arrayList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
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
