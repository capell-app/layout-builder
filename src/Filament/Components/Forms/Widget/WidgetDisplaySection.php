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
        return Forms\Components\Section::make(__('capell-admin::generic.display_settings'))
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

                Forms\Components\Select::make('max_width')
                    ->label(__('capell-admin::form.max_width'))
                    ->placeholder(__('capell-admin::generic.none'))
                    ->options([
                        'sm' => __('capell-admin::generic.sm'),
                        'md' => __('capell-admin::generic.md'),
                        'lg' => __('capell-admin::generic.lg'),
                        'xl' => __('capell-admin::generic.xl'),
                        '2xl' => __('capell-admin::generic.2xl'),
                        '3xl' => __('capell-admin::generic.3xl'),
                    ]),

                ContainerWidthSelect::make('container'),

                AlignSelect::make('align'),

                BackgroundSettingsFieldset::make(),
            ]);
    }
}
