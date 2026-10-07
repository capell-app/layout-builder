<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class WidgetDisplayTab
{
    /**
     * @param  array<int, Component | Action | ActionGroup | string | Htmlable>  $configurator
     */
    public static function make(array $configurator = []): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.display'))
            ->icon(Heroicon::OutlinedSparkles)
            ->columns()
            ->schema($configurator);
    }
}
