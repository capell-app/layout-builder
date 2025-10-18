<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\ContentPresenterSelect;
use Capell\Layout\Filament\Components\Forms\AlignSelect;
use Capell\Layout\Filament\Components\Forms\BackgroundSchema;
use Capell\Layout\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Layout\Filament\Components\Forms\MarginSelect;
use Capell\Layout\Filament\Components\Forms\PaddingSelect;
use Capell\Layout\Filament\Components\Forms\SizeSelect;
use Capell\Layout\Filament\Components\Forms\SpacingSelect;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class WidgetDisplaySection
{
    public static function make(array $schema = []): Section
    {
        return Section::make(__('capell-admin::generic.display_settings'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->collapsed()
            ->compact()
            ->columnSpanFull()
            ->schema([
                Grid::make(3)
                    ->statePath('meta')
                    ->schema([
                        ...$schema,
                        ContentPresenterSelect::make(),
                        PaddingSelect::make('padding'),
                        MarginSelect::make('margin'),
                        SpacingSelect::make('spacing'),
                        SizeSelect::make('size'),
                        Select::make('max_width')
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
                        ContainerWidthSelect::make(),
                        AlignSelect::make('align'),
                    ]),
                ...BackgroundSchema::make(),
            ]);
    }
}
