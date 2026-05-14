<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Models\Layout;
use Illuminate\Support\Facades\Schema;

class LayoutSavingListener
{
    public function __invoke(Layout $layout): void
    {
        if (! Schema::hasColumn('layouts', 'widgets')) {
            return;
        }

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $layout->setAttribute('widgets', collect($containers)
            ->flatMap(fn (array $container): array => $container['widgets'] ?? [])
            ->unique('widget_key')
            ->pluck('widget_key')
            ->all());
    }
}
