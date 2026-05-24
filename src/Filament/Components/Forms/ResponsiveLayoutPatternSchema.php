<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;

class ResponsiveLayoutPatternSchema
{
    /**
     * @return array<array-key, mixed>
     */
    public static function make(): array
    {
        return [
            ResponsiveLayoutPatternSelect::make('responsive_layout_pattern'),
            Fieldset::make(__('capell-layout-builder::form.responsive_grid_options'))
                ->columnSpanFull()
                ->columns(['default' => 2, 'lg' => 5])
                ->schema([
                    ColumnInput::make('responsive_grid_sm_columns')
                        ->label(__('capell-layout-builder::form.responsive_grid_sm_columns'))
                        ->default(2)
                        ->placeholder('2'),
                    ColumnInput::make('responsive_grid_md_columns')
                        ->label(__('capell-layout-builder::form.responsive_grid_md_columns'))
                        ->default(4)
                        ->placeholder('4'),
                    ColumnInput::make('responsive_grid_lg_columns')
                        ->label(__('capell-layout-builder::form.responsive_grid_lg_columns'))
                        ->placeholder('4'),
                    ColumnInput::make('responsive_grid_xl_columns')
                        ->label(__('capell-layout-builder::form.responsive_grid_xl_columns'))
                        ->placeholder('4'),
                    ColumnInput::make('responsive_grid_rows')
                        ->label(__('capell-layout-builder::form.responsive_grid_rows'))
                        ->helperText(__('capell-layout-builder::generic.responsive_grid_rows_helper'))
                        ->placeholder(__('capell-admin::generic.none')),
                ])
                ->visible(fn (Get $get): bool => self::pattern($get)->usesDesktopGrid()),
            Fieldset::make(__('capell-layout-builder::form.responsive_carousel_options'))
                ->columnSpanFull()
                ->columns(['default' => 2, 'lg' => 4])
                ->schema([
                    TextInput::make('responsive_carousel_mobile_slides')
                        ->label(__('capell-layout-builder::form.responsive_carousel_mobile_slides'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(6)
                        ->default('1.1')
                        ->placeholder('1.1'),
                    TextInput::make('responsive_carousel_sm_slides')
                        ->label(__('capell-layout-builder::form.responsive_carousel_sm_slides'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(6)
                        ->default('2')
                        ->placeholder('2'),
                    ColumnInput::make('responsive_carousel_rows')
                        ->label(__('capell-layout-builder::form.responsive_carousel_rows'))
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(4),
                    AlignSelect::make('carousel_align')
                        ->label(__('capell-layout-builder::form.carousel_align')),
                    Checkbox::make('responsive_carousel_highlight_active')
                        ->label(__('capell-layout-builder::form.responsive_carousel_highlight_active')),
                    Checkbox::make('carousel_arrows')
                        ->label(__('capell-layout-builder::form.carousel_arrows')),
                    Checkbox::make('carousel_pagination')
                        ->label(__('capell-layout-builder::form.carousel_pagination'))
                        ->default(true),
                    Checkbox::make('carousel_loop')
                        ->label(__('capell-layout-builder::form.carousel_loop')),
                    Checkbox::make('carousel_rewind')
                        ->label(__('capell-layout-builder::form.carousel_rewind'))
                        ->visible(fn (Get $get): bool => ! (bool) $get('carousel_loop')),
                    Checkbox::make('carousel_drag')
                        ->label(__('capell-layout-builder::form.carousel_drag'))
                        ->default(true),
                    Checkbox::make('carousel_touch')
                        ->label(__('capell-layout-builder::form.carousel_touch'))
                        ->default(true),
                    Checkbox::make('carousel_auto_play')
                        ->label(__('capell-layout-builder::form.carousel_auto_play'))
                        ->reactive(),
                    Checkbox::make('carousel_pause_on_hover')
                        ->label(__('capell-layout-builder::form.carousel_pause_on_hover'))
                        ->visible(fn (Get $get): bool => (bool) $get('carousel_auto_play')),
                    Checkbox::make('carousel_disable_on_interaction')
                        ->label(__('capell-layout-builder::form.carousel_disable_on_interaction'))
                        ->visible(fn (Get $get): bool => (bool) $get('carousel_auto_play')),
                    TextInput::make('carousel_auto_delay')
                        ->label(__('capell-layout-builder::form.carousel_auto_delay'))
                        ->numeric()
                        ->suffix(__('capell-admin::generic.milliseconds'))
                        ->default(5000)
                        ->placeholder('5000')
                        ->visible(fn (Get $get): bool => (bool) $get('carousel_auto_play')),
                    TextInput::make('carousel_speed')
                        ->label(__('capell-layout-builder::form.carousel_speed'))
                        ->numeric()
                        ->suffix(__('capell-admin::generic.milliseconds'))
                        ->default(300)
                        ->placeholder('300'),
                ])
                ->visible(fn (Get $get): bool => self::pattern($get)->usesMobileCarousel()),
        ];
    }

    private static function pattern(Get $get): ResponsiveLayoutPattern
    {
        return ResponsiveLayoutPattern::fromNullable($get('responsive_layout_pattern'));
    }
}
