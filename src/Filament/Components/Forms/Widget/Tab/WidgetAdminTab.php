<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Capell\LayoutBuilder\Filament\Components\Forms\Widget\AdminSchema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;

class WidgetAdminTab
{
    /**
     * @param  array<int, Component | Action | ActionGroup | string | Htmlable>  $configurator
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
