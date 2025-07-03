<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget\Tab;

use Filament\Forms;

class WidgetDisplayTab
{
    public static function make(array $schema = []): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('capell-admin::tab.settings'))
            ->icon('heroicon-o-wrench')
            ->schema($schema);
    }
}
