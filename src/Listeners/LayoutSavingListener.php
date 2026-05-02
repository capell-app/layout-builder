<?php

declare(strict_types=1);

namespace Capell\Mosaic\Listeners;

use Capell\Core\Models\Layout;
use Illuminate\Support\Facades\Schema;

class LayoutSavingListener
{
    public function __invoke(Layout $layout): void
    {
        if (! Schema::hasColumn('layouts', 'widgets')) {
            return;
        }

        $layout->widgets = collect($layout->containers)
            ->flatMap(fn (array $container): array => $container['widgets'] ?? [])
            ->unique('widget_key')
            ->pluck('widget_key')
            ->all();
    }
}
