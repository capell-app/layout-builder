<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Card Grid Widget
 *
 * Enables admins to create responsive card grids with customizable cards,
 * columns, variants, and layout options.
 */
class ModernCardGridConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_header'))
                ->description(__('capell-layout-builder::blocks.modern.card_grid.section_header_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.title_placeholder'))
                        ->maxLength(100),

                    Textarea::make('data.description')
                        ->label(__('capell-layout-builder::blocks.modern.card_grid.description_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.description_placeholder'))
                        ->rows(2),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_cards'))
                ->description(__('capell-layout-builder::blocks.common.section_cards_description'))
                ->schema([
                    Repeater::make('data.cards')
                        ->label(__('capell-layout-builder::blocks.modern.card_grid.cards_label'))
                        ->schema([
                            TextInput::make('icon')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_icon_label'))
                                ->placeholder('🎨')
                                ->maxLength(2)
                                ->helperText(__('capell-layout-builder::blocks.modern.card_grid.card_icon_helper')),

                            TextInput::make('title')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_title_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.card_title_placeholder'))
                                ->required()
                                ->maxLength(50),

                            Textarea::make('description')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_description_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.card_description_placeholder'))
                                ->rows(2)
                                ->maxLength(200),

                            TextInput::make('image')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_image_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.card_image_placeholder'))
                                ->url()
                                ->helperText(__('capell-layout-builder::blocks.modern.card_grid.card_image_helper')),

                            TextInput::make('badge')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_badge_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.card_badge_placeholder'))
                                ->maxLength(30)
                                ->helperText(__('capell-layout-builder::blocks.modern.card_grid.card_badge_helper')),

                            TextInput::make('link.label')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_link_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.card_link_placeholder'))
                                ->maxLength(30),

                            TextInput::make('link.url')
                                ->label(__('capell-layout-builder::blocks.modern.card_grid.card_link_url_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.card_grid.card_link_url_placeholder'))
                                ->url(),
                        ])
                        ->columns(2)
                        ->defaultItems(3)
                        ->minItems(1)
                        ->maxItems(12)
                        ->addActionLabel(__('capell-layout-builder::blocks.modern.card_grid.add_card')),
                ])->columns(1),

            Section::make(__('capell-layout-builder::blocks.common.section_layout'))
                ->description(__('capell-layout-builder::blocks.modern.card_grid.section_layout_description'))
                ->schema([
                    Select::make('data.columns')
                        ->label(__('capell-layout-builder::blocks.modern.card_grid.columns_label'))
                        ->options([
                            2 => __('capell-layout-builder::blocks.common.columns_2'),
                            3 => __('capell-layout-builder::blocks.common.columns_3'),
                            4 => __('capell-layout-builder::blocks.common.columns_4'),
                        ])
                        ->default(3)
                        ->helperText(__('capell-layout-builder::blocks.modern.card_grid.columns_helper')),

                    Select::make('data.variant')
                        ->label(__('capell-layout-builder::blocks.modern.card_grid.variant_label'))
                        ->options([
                            'default' => __('capell-layout-builder::blocks.modern.card_grid.variant_default'),
                            'elevated' => __('capell-layout-builder::blocks.modern.card_grid.variant_elevated'),
                            'glass' => __('capell-layout-builder::blocks.modern.card_grid.variant_glass'),
                        ])
                        ->default('default')
                        ->helperText(__('capell-layout-builder::blocks.modern.card_grid.variant_helper')),

                    Select::make('data.accentColor')
                        ->label(__('capell-layout-builder::blocks.common.accent_color_label'))
                        ->options([
                            'primary' => __('capell-layout-builder::blocks.common.accent_violet'),
                            'secondary' => __('capell-layout-builder::blocks.common.accent_indigo'),
                            'tertiary' => __('capell-layout-builder::blocks.common.accent_gold'),
                        ])
                        ->default('primary')
                        ->helperText(__('capell-layout-builder::blocks.modern.card_grid.accent_helper')),

                    Select::make('data.hoverEffect')
                        ->label(__('capell-layout-builder::blocks.modern.card_grid.hover_label'))
                        ->options([
                            'scale' => __('capell-layout-builder::blocks.modern.card_grid.hover_scale'),
                            'shadow' => __('capell-layout-builder::blocks.modern.card_grid.hover_shadow'),
                            'lift' => __('capell-layout-builder::blocks.modern.card_grid.hover_lift'),
                        ])
                        ->default('scale')
                        ->helperText(__('capell-layout-builder::blocks.modern.card_grid.hover_helper')),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_display'))
                ->description(__('capell-layout-builder::blocks.common.section_display_description'))
                ->schema([
                    Toggle::make('data.customizable')
                        ->label(__('capell-layout-builder::blocks.common.admin_hints_label'))
                        ->default(true),
                ])->columns(1),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'title' => 'Featured Blocks',
            'description' => 'Choose from our collection of modern, customizable components',
            'cards' => [
                [
                    'icon' => '🎨',
                    'title' => 'Design System',
                    'description' => 'Modern tokens and components',
                    'image' => null,
                    'link' => ['label' => 'Learn More', 'url' => '#'],
                ],
                [
                    'icon' => '⚡',
                    'title' => 'Performance',
                    'description' => 'Lightning-fast rendering',
                    'image' => null,
                    'link' => ['label' => 'Learn More', 'url' => '#'],
                ],
                [
                    'icon' => '🔧',
                    'title' => 'Customizable',
                    'description' => 'Endless possibilities',
                    'image' => null,
                    'link' => ['label' => 'Learn More', 'url' => '#'],
                ],
            ],
            'columns' => 3,
            'variant' => 'default',
            'accentColor' => 'primary',
            'hoverEffect' => 'scale',
            'customizable' => true,
        ];
    }
}
