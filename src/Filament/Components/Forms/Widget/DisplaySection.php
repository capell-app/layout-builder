<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\LayoutBuilder\Filament\Components\Forms\AlignSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\BackgroundSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\ContainerWidthSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\HeadingStyleSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\MarginSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\PaddingSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\SizeSelect;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class DisplaySection
{
    public static function make(array $configurator = []): Section
    {
        return Section::make(__('capell-admin::generic.display_settings'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->collapsed()
            ->compact()
            ->columnSpanFull()
            ->columns(1)
            ->schema([
                Grid::make(3)
                    ->statePath('meta')
                    ->schema([
                        ...$configurator,
                        PaddingSelect::make('padding'),
                        MarginSelect::make('margin'),
                        SizeSelect::make('size'),
                        Select::make('max_width')
                            ->label(__('capell-layout-builder::form.max_width'))
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
                        HeadingStyleSelect::make('heading_style'),
                        Select::make('content_divider')
                            ->label(__('capell-layout-builder::form.content_divider'))
                            ->helperText(__('capell-layout-builder::generic.content_divider_helper'))
                            ->options([
                                'none' => __('capell-admin::generic.none'),
                                'below_heading' => __('capell-layout-builder::generic.below_heading'),
                                'above_heading' => __('capell-layout-builder::generic.above_heading'),
                                'below_content' => __('capell-layout-builder::generic.below_content'),
                            ]),
                    ]),
                Checkbox::make('background')
                    ->label(__('capell-layout-builder::form.background'))
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Checkbox $component, Get $get): void {
                        $hasBackground = (bool) $get('background_color') || (bool) $get('background_image');
                        $component->state($hasBackground);
                    }),
                Fieldset::make(__('capell-admin::generic.background_settings'))
                    ->columns(3)
                    ->visibleJs(<<<'JS'
                        $get('background')
                    JS)
                    ->schema(BackgroundSchema::make()),
            ]);
    }
}
