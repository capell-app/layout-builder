<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

final class CountLinkedLayoutPresetUsagesAction
{
    use AsObject;

    public function handle(LayoutPreset $preset, ?Layout $excludingLayout = null): int
    {
        $excludingLayoutKey = $excludingLayout?->getKey();

        return LayoutPresetUsage::query()
            ->where('preset_id', $preset->getKey())
            ->when(
                $excludingLayout instanceof Layout,
                static fn (Builder $query): Builder => $query->where('layout_id', '!=', $excludingLayoutKey),
            )
            ->count();
    }
}
