<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Enums\LayoutPresetSyncResultStatus;
use Capell\LayoutBuilder\Enums\LayoutPresetSyncRunStatus;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetSyncResult;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static void run(LayoutPresetSyncRun $run) */
final class RunLinkedLayoutPresetSyncAction
{
    use AsObject;

    public function handle(LayoutPresetSyncRun $run): void
    {
        $preset = $run->preset()->first();

        if (! $preset instanceof LayoutPreset
            || $preset->mode !== LayoutPresetMode::Linked
            || $preset->revision !== $run->revision) {
            $this->complete($run, LayoutPresetSyncRunStatus::Cancelled, []);

            return;
        }

        $run->forceFill([
            'status' => LayoutPresetSyncRunStatus::Running,
            'started_at' => Date::now(),
        ])->save();

        $summary = [
            LayoutPresetSyncResultStatus::Updated->value => 0,
            LayoutPresetSyncResultStatus::Skipped->value => 0,
            LayoutPresetSyncResultStatus::Conflict->value => 0,
            LayoutPresetSyncResultStatus::AssetConflict->value => 0,
            LayoutPresetSyncResultStatus::Detached->value => 0,
        ];

        LayoutPresetUsage::query()
            ->where('preset_id', $preset->getKey())
            ->when($run->excluded_usage_id !== null, static fn (Builder $query): Builder => $query->whereKeyNot($run->excluded_usage_id))
            ->orderBy('id')
            ->chunkById(100, function (Collection $usages) use ($run, $preset, &$summary): void {
                foreach ($usages as $usage) {
                    if (! $usage instanceof LayoutPresetUsage) {
                        continue;
                    }

                    $status = $this->syncUsage($run, $preset, $usage);
                    $summary[$status->value]++;
                }
            });

        $hasConflicts = $summary[LayoutPresetSyncResultStatus::Conflict->value] > 0
            || $summary[LayoutPresetSyncResultStatus::AssetConflict->value] > 0;

        $this->complete(
            $run,
            $hasConflicts ? LayoutPresetSyncRunStatus::CompletedWithConflicts : LayoutPresetSyncRunStatus::Completed,
            $summary,
        );
    }

    private function syncUsage(LayoutPresetSyncRun $run, LayoutPreset $preset, LayoutPresetUsage $usage): LayoutPresetSyncResultStatus
    {
        $result = DB::transaction(function () use ($run, $preset, $usage): array {
            $lockedPreset = LayoutPreset::query()->lockForUpdate()->find($preset->getKey());
            if (! $lockedPreset instanceof LayoutPreset || $lockedPreset->revision !== $run->revision) {
                return [LayoutPresetSyncResultStatus::Skipped, 'obsolete_revision'];
            }

            $lockedUsage = LayoutPresetUsage::query()->lockForUpdate()->find($usage->getKey());
            if (! $lockedUsage instanceof LayoutPresetUsage) {
                return [LayoutPresetSyncResultStatus::Skipped, 'usage_removed'];
            }

            $layout = Layout::query()->lockForUpdate()->find($lockedUsage->layout_id);
            if (! $layout instanceof Layout) {
                return [LayoutPresetSyncResultStatus::Skipped, 'layout_missing'];
            }

            if (! $this->matchesUsageVersion($layout, $lockedUsage)) {
                return [LayoutPresetSyncResultStatus::Conflict, 'layout_changed'];
            }

            $containers = is_array($layout->containers) ? $layout->containers : [];
            $existingContainer = $containers[$lockedUsage->container_key] ?? null;
            if (! is_array($existingContainer)) {
                return [LayoutPresetSyncResultStatus::Detached, 'container_missing'];
            }

            $meta = is_array($existingContainer['meta'] ?? null) ? $existingContainer['meta'] : [];
            $link = LayoutPresetLinkData::fromMeta($meta);
            if ($link === null || ! $link->matches($lockedPreset) || $link->presetItemId !== $lockedUsage->preset_item_id) {
                return [LayoutPresetSyncResultStatus::Detached, 'link_removed'];
            }

            $item = $this->snapshotItem($lockedPreset, $lockedUsage->preset_item_id);
            if ($item === null) {
                return [LayoutPresetSyncResultStatus::Skipped, 'preset_item_missing'];
            }

            $replacement = $item['container'];
            if ($this->hasPageAssetConflict($layout, $lockedUsage->container_key, $existingContainer, $replacement)) {
                return [LayoutPresetSyncResultStatus::AssetConflict, 'page_assets_would_be_orphaned'];
            }

            $replacement = $this->preserveAnchors($containers, $lockedUsage->container_key, $existingContainer, $replacement);
            $replacement = LinkLayoutPresetContainerAction::run($replacement, $link);
            $containers[$lockedUsage->container_key] = $replacement;
            $layout->forceFill(['containers' => $containers])->save();

            return [LayoutPresetSyncResultStatus::Updated, null];
        });

        [$status, $reason] = $result;

        if ($status === LayoutPresetSyncResultStatus::Updated) {
            $layout = Layout::query()->find($usage->layout_id);

            if ($layout instanceof Layout) {
                InvalidateLayoutPreviewImageAction::run($layout);
                $usage->forceFill(['layout_updated_at' => $layout->fresh()?->updated_at])->save();
            }
        }

        LayoutPresetSyncResult::query()->updateOrCreate(
            [
                'run_id' => $run->getKey(),
                'usage_id' => $usage->getKey(),
            ],
            [
                'layout_id' => $usage->layout_id,
                'container_key' => $usage->container_key,
                'status' => $status,
                'reason' => $reason,
            ],
        );

        return $status;
    }

    /**
     * @return array{container: array<string, mixed>}|null
     */
    private function snapshotItem(LayoutPreset $preset, string $presetItemId): ?array
    {
        $snapshot = is_array($preset->snapshot) ? $preset->snapshot : [];
        $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];

        foreach ($items as $item) {
            if (! is_array($item)
                || ($item['id'] ?? null) !== $presetItemId
                || ! is_array($item['container'] ?? null)) {
                continue;
            }

            /** @var array<string, mixed> $container */
            $container = $item['container'];

            return ['container' => $container];
        }

        return null;
    }

    private function matchesUsageVersion(Layout $layout, LayoutPresetUsage $usage): bool
    {
        if ($usage->layout_updated_at === null || $layout->updated_at === null) {
            return $usage->layout_updated_at === $layout->updated_at;
        }

        return (string) $usage->layout_updated_at === (string) $layout->updated_at;
    }

    /**
     * @param  array<string, mixed>  $existingContainer
     * @param  array<string, mixed>  $replacement
     */
    private function hasPageAssetConflict(Layout $layout, string $containerKey, array $existingContainer, array $replacement): bool
    {
        $removedSlots = array_diff_key($this->widgetSlots($existingContainer), $this->widgetSlots($replacement));
        if ($removedSlots === []) {
            return false;
        }

        $pageIds = $layout->pages()->pluck('id')->all();
        if ($pageIds === []) {
            return false;
        }

        $widgetKeys = array_values(array_unique(array_map(static fn (array $slot): string => $slot['widget_key'], $removedSlots)));
        $widgetIds = Widget::query()->whereIn('key', $widgetKeys)->pluck('id')->all();
        if ($widgetIds === []) {
            return false;
        }

        $removedOccurrences = array_values(array_unique(array_map(static fn (array $slot): int => $slot['occurrence'], $removedSlots)));

        return WidgetAsset::query()
            ->whereIn('widget_id', $widgetIds)
            ->where('container', $containerKey)
            ->where('pageable_type', (new Page)->getMorphClass())
            ->whereIn('pageable_id', $pageIds)
            ->whereIn('occurrence', $removedOccurrences)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, array{widget_key: string, occurrence: int}>
     */
    private function widgetSlots(array $container): array
    {
        $slots = [];
        $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

        foreach ($widgets as $widget) {
            if (! is_array($widget) || ! is_string($widget['widget_key'] ?? null)) {
                continue;
            }

            $occurrence = is_numeric($widget['occurrence'] ?? null) ? (int) $widget['occurrence'] : 1;
            $slots[$widget['widget_key'] . ':' . $occurrence] = [
                'widget_key' => $widget['widget_key'],
                'occurrence' => $occurrence,
            ];
        }

        return $slots;
    }

    /**
     * @param  array<string, mixed>  $containers
     * @param  array<string, mixed>  $existingContainer
     * @param  array<string, mixed>  $replacement
     * @return array<string, mixed>
     */
    private function preserveAnchors(array $containers, string $containerKey, array $existingContainer, array $replacement): array
    {
        $existingAnchors = [];
        foreach ($this->widgets($existingContainer) as $widget) {
            $anchor = data_get($widget, 'meta.widget_settings.anchor_id');
            if (is_string($anchor) && trim($anchor) !== '') {
                $existingAnchors[$this->slotKey($widget)] = $anchor;
            }
        }

        $usedAnchors = [];
        foreach ($containers as $key => $container) {
            if ($key === $containerKey || ! is_array($container)) {
                continue;
            }

            /** @var array<string, mixed> $typedContainer */
            $typedContainer = $container;
            foreach ($this->widgets($typedContainer) as $widget) {
                $anchor = data_get($widget, 'meta.widget_settings.anchor_id');
                if (is_string($anchor) && trim($anchor) !== '') {
                    $usedAnchors[Str::slug($anchor)] = true;
                }
            }
        }

        $widgets = $this->widgets($replacement);
        foreach ($widgets as $index => $widget) {
            $slotKey = $this->slotKey($widget);
            $anchor = $existingAnchors[$slotKey] ?? data_get($widget, 'meta.widget_settings.anchor_id');
            if (! is_string($anchor) || trim($anchor) === '') {
                continue;
            }

            $candidate = Str::slug($anchor);
            $suffix = 2;
            while (isset($usedAnchors[$candidate])) {
                $candidate = Str::slug($anchor) . '-' . $suffix;
                $suffix++;
            }

            data_set($widgets[$index], 'meta.widget_settings.anchor_id', $candidate);
            $usedAnchors[$candidate] = true;
        }

        $replacement['widgets'] = $widgets;

        return $replacement;
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<int, array<string, mixed>>
     */
    private function widgets(array $container): array
    {
        $widgets = $container['widgets'] ?? [];

        if (! is_array($widgets)) {
            return [];
        }

        $validWidgets = [];
        foreach ($widgets as $widget) {
            if (is_array($widget)) {
                /** @var array<string, mixed> $widget */
                $validWidgets[] = $widget;
            }
        }

        return $validWidgets;
    }

    /** @param array<string, mixed> $widget */
    private function slotKey(array $widget): string
    {
        $widgetKey = $widget['widget_key'] ?? '';

        return (is_string($widgetKey) ? $widgetKey : '') . ':' . (is_numeric($widget['occurrence'] ?? null) ? (int) $widget['occurrence'] : 1);
    }

    /** @param array<string, int> $summary */
    private function complete(LayoutPresetSyncRun $run, LayoutPresetSyncRunStatus $status, array $summary): void
    {
        $run->forceFill([
            'status' => $status,
            'summary' => $summary,
            'completed_at' => Date::now(),
        ])->save();
    }
}
