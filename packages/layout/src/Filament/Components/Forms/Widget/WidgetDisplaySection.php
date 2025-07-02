<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AlignSelect;
use Capell\Admin\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Admin\Filament\Components\Forms\MarginSelect;
use Capell\Admin\Filament\Components\Forms\PaddingSelect;
use Capell\Admin\Filament\Components\Forms\SizeSelect;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Filament\Forms;

class WidgetDisplaySection
{
    public static function make(array $schema = []): Forms\Components\Section
    {
        return Forms\Components\Section::make(__('capell-admin::generic.display'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->collapsed()
            ->compact()
            ->columnSpanFull()
            ->columns(3)
            ->schema([
                ...$schema,

                PaddingSelect::make('padding'),

                MarginSelect::make('margin'),

                SizeSelect::make('size'),

                ContainerWidthSelect::make('container'),

                AlignSelect::make('align'),

                BackgroundSettingsFieldset::make(),
            ]);
    }
}
