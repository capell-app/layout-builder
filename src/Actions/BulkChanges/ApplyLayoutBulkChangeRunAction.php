<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

final class ApplyLayoutBulkChangeRunAction
{
    use AsAction;

    /** @return array<string, mixed> */
    public function handle(LayoutBulkChangeRun $run, ?int $actorId = null): array
    {
        if ($run->status === LayoutBulkChangeRunStatus::Blocked) {
            throw new LogicException('This bulk layout change is blocked by preview warnings and cannot be applied.');
        }

        if (in_array($run->status, [LayoutBulkChangeRunStatus::Applied, LayoutBulkChangeRunStatus::PartiallyApplied], true)) {
            return $run->summary ?? [];
        }

        return DB::transaction(function () use ($run, $actorId): array {
            $applied = 0;
            $drifted = 0;
            $skipped = 0;

            $results = LayoutBulkChangeResult::query()->where('run_id', $run->id)->where('status', LayoutBulkChangeResultStatus::Changed)->lockForUpdate()->get();

            foreach ($results as $result) {
                $layout = $result->layout_id === null ? null : Layout::query()->whereKey($result->layout_id)->lockForUpdate()->first();

                if (! $layout instanceof Layout) {
                    $skipped++;
                    $result->update(['status' => LayoutBulkChangeResultStatus::Skipped, 'skipped_reason' => 'The layout no longer exists.']);

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
                $applied++;
                $result->update(['status' => LayoutBulkChangeResultStatus::Applied, 'applied_at' => Carbon::now()]);
            }

            $summary = [...($run->summary ?? []), 'applied_layouts' => $applied, 'drifted_layouts' => $drifted, 'apply_skipped_layouts' => $skipped];

            $run->forceFill([
                'status' => ($drifted > 0 || $skipped > 0) ? LayoutBulkChangeRunStatus::PartiallyApplied : LayoutBulkChangeRunStatus::Applied,
                'summary' => $summary,
                'approved_by' => $actorId,
                'applied_by' => $actorId,
                'approved_at' => Carbon::now(),
                'applied_at' => Carbon::now(),
            ])->save();

            return $summary;
        });
    }

    private function migratePageScopedAssets(Layout $layout, LayoutBulkChangeResult $result): void
    {
        $changes = $result->changes ?? [];

        foreach ((array) ($changes['asset_moves'] ?? []) as $assetMove) {
            if (! is_array($assetMove)) {
                continue;
            }

            $widget = Widget::query()->where('key', (string) ($assetMove['widget_key'] ?? ''))->first();

            if (! $widget instanceof Widget) {
                continue;
            }

            foreach ($this->pageScopesForLayout($layout) as $pageScope) {
                WidgetAsset::query()
                    ->where('widget_id', $widget->id)
                    ->where('container', $assetMove['from_container'])
                    ->where('occurrence', (int) ($assetMove['from_occurrence'] ?? 1))
                    ->where('pageable_type', $pageScope['type'])
                    ->where('pageable_id', $pageScope['id'])
                    ->update(['container' => $assetMove['to_container'], 'occurrence' => (int) ($assetMove['to_occurrence'] ?? 1)]);
            }
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
                $scopes[] = ['type' => $page->getMorphClass(), 'id' => $page->getKey()];
            });
        }

        return $scopes;
    }
}
