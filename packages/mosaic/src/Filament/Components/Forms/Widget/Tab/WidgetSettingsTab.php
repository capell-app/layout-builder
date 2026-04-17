<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget\Tab;

use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WidgetSettingsTab
{
    public static function make(Schema $schema): Tab
    {
        return Tab::make(__('capell-admin::tab.settings'))
            ->icon(Heroicon::OutlinedCog)
            ->columns()
            ->schema(SettingsSchema::make($schema));
    }
}
