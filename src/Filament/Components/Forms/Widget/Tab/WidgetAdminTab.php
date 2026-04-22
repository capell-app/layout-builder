<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Widget\Tab;

use Capell\Mosaic\Filament\Components\Forms\Widget\AdminSchema;
use Filament\Schemas\Components\Tabs\Tab;

class WidgetAdminTab
{
    public static function make(array $schema = []): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columns(['md' => 2])
            ->schema([
                ...AdminSchema::make(),
                ...$schema,
            ]);
    }
}
