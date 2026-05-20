<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab;

use Capell\LayoutBuilder\Filament\Components\Forms\Block\SettingsSchema;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BlockSettingsTab
{
    public static function make(Schema $configurator): Tab
    {
        return Tab::make(__('capell-admin::tab.settings'))
            ->icon(Heroicon::OutlinedCog)
            ->columns()
            ->schema(SettingsSchema::make($configurator));
    }
}
