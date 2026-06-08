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
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array<string, mixed> run(LayoutBulkChangeRun $run, ?int $actorId = null)
 */
final class RevertLayoutBulkChangeRunAction
{
    use AsAction;

    /** @return array<string, mixed> */
    public function handle(LayoutBulkChangeRun $run, ?int $actorId = null): array
    {
        throw_unless(in_array($run->status, [LayoutBulkChangeRunStatus::Applied, LayoutBulkChangeRunStatus::PartiallyApplied], true), LogicException::class, 'Only applied bulk layout changes can be reverted.');

        return DB::transaction(function () use ($run, $actorId): array {
            $reverted = 0;
            $drifted = 0;

            LayoutBulkChangeResult::query()
                ->where('run_id', $run->id)
                ->where('status', LayoutBulkChangeResultStatus::Applied)
                ->lockForUpdate()
                ->get()
                ->each(function (LayoutBulkChangeResult $result) use (&$reverted, &$drifted): void {
                    $layout = $result->layout_id === null
                        ? null
                        : Layout::query()->whereKey($result->layout_id)->lockForUpdate()->first();

                    if (! $layout instanceof Layout) {
                        $drifted++;
                        $result->update([
                            'status' => LayoutBulkChangeResultStatus::RevertDrifted,
                            'skipped_reason' => 'The layout no longer exists.',
                        ]);

                        return;
                    }

                    $current = is_array($layout->containers) ? $layout->containers : [];

                    if (PreviewLayoutBulkChangeAction::hashContainers($current) !== $result->proposed_container_hash) {
                        $drifted++;
                        $result->update([
                            'status' => LayoutBulkChangeResultStatus::RevertDrifted,
                            'skipped_reason' => 'The layout changed after this run was applied.',
                        ]);

                        return;
                    }

                    $this->restoreMovedPageScopedAssets($layout, $result);

                    $layout->containers = $result->original_containers ?? [];
                    $layout->save();

                    $reverted++;
                    $result->update([
                        'status' => LayoutBulkChangeResultStatus::Reverted,
                        'reverted_at' => Date::now(),
                    ]);
                });

            $summary = [
                ...($run->summary ?? []),
                'reverted_layouts' => $reverted,
                'revert_drifted_layouts' => $drifted,
            ];

            $run->forceFill([
                'status' => $drifted > 0 ? LayoutBulkChangeRunStatus::PartiallyReverted : LayoutBulkChangeRunStatus::Reverted,
                'summary' => $summary,
                'reverted_by' => $actorId,
                'reverted_at' => Date::now(),
            ])->save();

            return $summary;
        });
    }

    private function restoreMovedPageScopedAssets(Layout $layout, LayoutBulkChangeResult $result): void
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
                    ->where('container', $this->stringValue($assetMove['to_container'] ?? null))
                    ->where('occurrence', $this->integerValue($assetMove['to_occurrence'] ?? null, 1))
                    ->where('pageable_type', $pageScope['type'])
                    ->where('pageable_id', $pageScope['id'])
                    ->update([
                        'container' => $this->stringValue($assetMove['from_container'] ?? null),
                        'occurrence' => $this->integerValue($assetMove['from_occurrence'] ?? null, 1),
                    ]);
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
