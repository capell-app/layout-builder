<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab;

use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\LayoutBuilder\Enums\BackgroundPosition;
use Capell\LayoutBuilder\Enums\BackgroundRepeat;
use Capell\LayoutBuilder\Enums\BackgroundSize;
use Capell\LayoutBuilder\Filament\Components\Forms\AlignSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\ContainerWidthSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\CustomColorInput;
use Capell\LayoutBuilder\Filament\Components\Forms\HeadingStyleSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\MarginSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\PaddingSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\ResponsiveLayoutPatternSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\SizeSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class WidgetPresentationTabs
{
    /**
     * @param  array<array-key, mixed>  $layoutSchema
     * @param  array<array-key, mixed>  $styleFields
     * @param  array<array-key, mixed>  $styleSchema
     * @param  array<array-key, mixed>  $itemsSchema
     * @param  array<array-key, mixed>  $appearanceSchema
     * @param  array<array-key, mixed>  $renderingSchema
     * @return array<int, Tab>
     */
    public static function make(
        array $layoutSchema = [],
        array $styleFields = [],
        array $styleSchema = [],
        bool $withComponentSection = true,
        array $itemsSchema = [],
        array $appearanceSchema = [],
        array $renderingSchema = [],
    ): array {
        $itemsSchema = [
            ...$layoutSchema,
            ...$itemsSchema,
        ];

        $appearanceSchema = [
            ...$styleSchema,
            ...$appearanceSchema,
        ];

        return [
            self::placementTab(),
            self::itemsTab($itemsSchema),
            self::appearanceTab($styleFields, $appearanceSchema),
            self::backgroundTab(),
            ...(($withComponentSection || $renderingSchema !== []) ? [self::renderingTab($renderingSchema, $withComponentSection)] : []),
        ];
    }

    public static function placementTab(): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.placement'))
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Grid::make(3)
                    ->statePath('meta')
                    ->schema([
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
                        SizeSelect::make('size'),
                        AlignSelect::make('align'),
                        PaddingSelect::make('padding')
                            ->helperText(__('capell-layout-builder::generic.padding_helper')),
                        MarginSelect::make('margin')
                            ->helperText(__('capell-layout-builder::generic.margin_helper')),
                    ]),
            ]);
    }

    /**
     * @param  array<array-key, mixed>  $schema
     */
    public static function itemsTab(array $schema = []): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.items'))
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                ...$schema,
                Grid::make(3)
                    ->statePath('meta')
                    ->schema([
                        ...ResponsiveLayoutPatternSchema::make(),
                    ]),
            ]);
    }

    /**
     * @param  array<array-key, mixed>  $fields
     * @param  array<array-key, mixed>  $schema
     */
    public static function appearanceTab(array $fields = [], array $schema = []): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.appearance'))
            ->icon('heroicon-o-swatch')
            ->schema([
                Grid::make(3)
                    ->statePath('meta')
                    ->schema([
                        ...$fields,
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
                ...$schema,
            ]);
    }

    public static function backgroundTab(): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.background'))
            ->icon('heroicon-o-photo')
            ->schema([
                ToggleButtons::make('background_mode')
                    ->label(__('capell-layout-builder::form.background_mode'))
                    ->inline()
                    ->grouped()
                    ->live()
                    ->dehydrated(false)
                    ->options([
                        'none' => __('capell-admin::generic.none'),
                        'color' => __('capell-layout-builder::form.background_mode_color'),
                        'image' => __('capell-layout-builder::form.background_mode_image'),
                        'color_image' => __('capell-layout-builder::form.background_mode_color_image'),
                    ])
                    ->afterStateHydrated(function (ToggleButtons $component, Get $get): void {
                        $hasColor = filled($get('meta.background_color'));
                        $hasImage = filled($get('background_image'));

                        $component->state(match (true) {
                            $hasColor && $hasImage => 'color_image',
                            $hasColor => 'color',
                            $hasImage => 'image',
                            default => 'none',
                        });
                    })
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        if ($state === 'none') {
                            $set('meta.background_color', null);
                            $set('background_image', null);

                            return;
                        }

                        if ($state === 'color') {
                            $set('background_image', null);

                            return;
                        }

                        if ($state === 'image') {
                            $set('meta.background_color', null);
                        }
                    }),

                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Group::make()
                            ->statePath('meta')
                            ->visible(fn (Get $get): bool => in_array($get('background_mode'), ['color', 'color_image'], true))
                            ->schema([
                                CustomColorInput::make(
                                    name: 'background_color',
                                    label: __('capell-admin::form.background_color'),
                                ),
                            ]),

                        MediaLibraryFileUpload::make('background_image')
                            ->label(__('capell-layout-builder::form.background_image'))
                            ->reactive()
                            ->visible(fn (Get $get): bool => in_array($get('background_mode'), ['image', 'color_image'], true)),
                    ]),

                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->statePath('meta')
                    ->visible(fn (Get $get): bool => in_array($get('background_mode'), ['image', 'color_image'], true) && filled($get('background_image')))
                    ->schema([
                        Select::make('background_size')
                            ->label(__('capell-layout-builder::form.background_size'))
                            ->default('cover')
                            ->options(BackgroundSize::class),
                        Select::make('background_position')
                            ->label(__('capell-layout-builder::form.background_position'))
                            ->default('center')
                            ->options(BackgroundPosition::class),
                        Select::make('background_repeat')
                            ->label(__('capell-layout-builder::form.background_repeat'))
                            ->default('no-repeat')
                            ->options(BackgroundRepeat::class),
                        Select::make('background_attachment')
                            ->label(__('capell-layout-builder::form.background_attachment'))
                            ->options([
                                'fixed' => __('capell-layout-builder::form.background_fixed'),
                                'scroll' => __('capell-layout-builder::form.background_scroll'),
                            ]),
                        Checkbox::make('background_overlay')
                            ->label(__('capell-layout-builder::form.background_overlay'))
                            ->helperText(__('capell-admin::generic.background_overlay_helper_text')),
                    ]),
            ]);
    }

    /**
     * @param  array<array-key, mixed>  $schema
     */
    public static function renderingTab(array $schema = [], bool $withComponentSection = true): Tab
    {
        return Tab::make(__('capell-layout-builder::tab.rendering'))
            ->icon('heroicon-o-puzzle-piece')
            ->schema([
                ...$schema,
                ...($withComponentSection ? [ComponentSection::make()] : []),
            ]);
    }
}
