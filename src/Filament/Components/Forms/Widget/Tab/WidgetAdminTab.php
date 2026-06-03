<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Capell\LayoutBuilder\Filament\Components\Forms\Widget\AdminSchema;
use Filament\Schemas\Components\Tabs\Tab;

class WidgetAdminTab
{
    /**
     * @param  array<array-key, mixed>  $configurator
     */
    public static function make(array $configurator = []): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columns(['md' => 2])
            ->schema([
                ...AdminSchema::make(),
                ...$configurator,
            ]);
    }
}
