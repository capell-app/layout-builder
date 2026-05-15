<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Models\Layout;
use Illuminate\Support\Facades\Schema;

class LayoutSavingListener
{
    public function __invoke(Layout $layout): void
    {
        if (! Schema::hasColumn('layouts', 'elements')) {
            return;
        }

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        $layout->setAttribute('elements', collect($containers)
            ->flatMap(fn (array $container): array => $container['elements'] ?? [])
            ->unique('element_key')
            ->pluck('element_key')
            ->all());
    }
}
