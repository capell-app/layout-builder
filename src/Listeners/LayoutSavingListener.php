<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Illuminate\Support\Facades\Schema;

class LayoutSavingListener
{
    public function __invoke(Layout $layout): void
    {
        if (! Schema::hasColumn('layouts', 'blocks')) {
            return;
        }

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $layout->setAttribute('blocks', collect($containers)
            ->flatMap(fn (array $container): array => LayoutBlockData::normalizeMany($container['blocks'] ?? []))
            ->map(fn (array $block): ?string => LayoutBlockData::key($block))
            ->filter()
            ->unique()
            ->values()
            ->all());
    }
}
