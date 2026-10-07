<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class WidgetSettingsTab
{
    /**
     * @param  array<int, Component | Action | ActionGroup | string | Htmlable>  $components
     */
    public static function make(Schema $configurator, array $components = []): Tab
    {
        return Tab::make(__('capell-admin::tab.settings'))
            ->icon(Heroicon::OutlinedCog)
            ->columns()
            ->schema(SettingsSchema::make($configurator, $components));
    }
}
