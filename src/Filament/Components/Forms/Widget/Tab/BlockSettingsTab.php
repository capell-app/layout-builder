<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BlockSettingsTab
{
    /**
     * @param  array<array-key, mixed>  $components
     */
    public static function make(Schema $configurator, array $components = []): Tab
    {
        return Tab::make(__('capell-admin::tab.settings'))
            ->icon(Heroicon::OutlinedCog)
            ->columns()
            ->schema(SettingsSchema::make($configurator, $components));
    }
}
