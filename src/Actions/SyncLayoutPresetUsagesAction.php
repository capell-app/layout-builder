<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class SyncLayoutPresetUsagesAction
{
    use AsObject;

    public function handle(Layout $layout): void
    {
        $containers = is_array($layout->containers) ? $layout->containers : [];
        $links = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_string($containerKey) || ! is_array($container)) {
                continue;
            }

            $meta = is_array($container['meta'] ?? null) ? $container['meta'] : [];
            $link = LayoutPresetLinkData::fromMeta($meta);

            if ($link !== null) {
                $links[$containerKey] = $link;
            }
        }

        DB::transaction(function () use ($layout, $links): void {
            $presetIds = array_values(array_unique(array_map(static fn (LayoutPresetLinkData $link): int => $link->presetId, $links)));
            $presets = LayoutPreset::query()
                ->whereIn('id', $presetIds)
                ->where('mode', LayoutPresetMode::Linked)
                ->get()
                ->keyBy('id');

            $activeContainerKeys = [];

            foreach ($links as $containerKey => $link) {
                $preset = $presets->get($link->presetId);

                if (! $preset instanceof LayoutPreset || ! $link->matches($preset) || (int) $preset->site_id !== (int) $layout->site_id) {
                    continue;
                }

                $activeContainerKeys[] = $containerKey;

                LayoutPresetUsage::query()->updateOrCreate(
                    [
                        'layout_id' => $layout->getKey(),
                        'container_key' => $containerKey,
                    ],
                    [
                        'preset_id' => $preset->getKey(),
                        'preset_item_id' => $link->presetItemId,
                        'layout_updated_at' => $layout->updated_at,
                    ],
                );
            }

            $usageQuery = LayoutPresetUsage::query()->where('layout_id', $layout->getKey());

            if ($activeContainerKeys === []) {
                $usageQuery->delete();

                return;
            }

            $usageQuery->whereNotIn('container_key', $activeContainerKeys)->delete();
        });
    }
}
