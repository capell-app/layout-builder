<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class BlockDisplayTab
{
    /**
     * @param  array<array-key, mixed>  $configurator
     */
    public static function make(array $configurator = []): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.display'))
            ->icon(Heroicon::OutlinedSparkles)
            ->columns()
            ->schema($configurator);
    }
}
