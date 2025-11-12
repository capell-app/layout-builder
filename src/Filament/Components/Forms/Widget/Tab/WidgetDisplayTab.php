<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget\Tab;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class WidgetDisplayTab
{
    public static function make(array $schema = []): Tab
    {
        return Tab::make(__('capell-admin::tab.display'))
            ->icon(Heroicon::OutlinedSparkles)
            ->columns()
            ->schema($schema);
    }
}
