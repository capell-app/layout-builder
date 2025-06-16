<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget\Tab;

use Capell\Layout\Filament\Components\Forms\Widget\WidgetAdminSchema;
use Filament\Forms;

class WidgetAdminTab
{
    public static function make(array $schema = []): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon('heroicon-o-cog')
            ->schema([
                ...WidgetAdminSchema::make(),
                ...$schema,
            ]);
    }
}
