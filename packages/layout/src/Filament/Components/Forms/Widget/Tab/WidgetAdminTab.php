<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget\Tab;

use Capell\Layout\Filament\Components\Forms\Widget\WidgetAdminSchema;
use Filament\Schemas\Components\Tabs\Tab;

class WidgetAdminTab
{
    public static function make(array $schema = []): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon('heroicon-o-cog-6-tooth')
            ->columns(['md' => 2])
            ->schema([
                ...WidgetAdminSchema::make(),
                ...$schema,
            ]);
    }
}
