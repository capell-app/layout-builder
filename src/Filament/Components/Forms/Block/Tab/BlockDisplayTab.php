<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class BlockDisplayTab
{
    public static function make(array $configurator = []): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.display'))
            ->icon(Heroicon::OutlinedSparkles)
            ->columns()
            ->schema($configurator);
    }
}
