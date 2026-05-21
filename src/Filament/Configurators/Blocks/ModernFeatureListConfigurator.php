<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Feature List Block
 *
 * Provides admin panel controls for customizing feature list layout
 * and display options.
 */
class ModernFeatureListConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.feature_list.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.feature_list.title_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::blocks.common.section_layout_display'))
                ->description(__('capell-layout-builder::blocks.common.section_layout_display_description'))
                ->schema([
                    Select::make('data.layout')
                        ->label(__('capell-layout-builder::blocks.modern.feature_list.layout_label'))
                        ->options([
                            'vertical' => __('capell-layout-builder::blocks.modern.feature_list.layout_vertical'),
                            'grid' => __('capell-layout-builder::blocks.modern.feature_list.layout_grid'),
                        ])
                        ->default('grid')
                        ->helperText(__('capell-layout-builder::blocks.modern.feature_list.layout_helper')),

                    Select::make('data.columns')
                        ->label(__('capell-layout-builder::blocks.common.grid_columns_label'))
                        ->options([
                            '2' => __('capell-layout-builder::blocks.common.columns_2'),
                            '3' => __('capell-layout-builder::blocks.common.columns_3'),
                            '4' => __('capell-layout-builder::blocks.common.columns_4'),
                        ])
                        ->default('3')
                        ->visible(fn (callable $get): bool => $get('data.layout') === 'grid'),

                    Select::make('data.animation')
                        ->label(__('capell-layout-builder::blocks.modern.feature_list.animation_label'))
                        ->options([
                            'fade-in' => __('capell-layout-builder::blocks.modern.feature_list.animation_fade'),
                            'slide-up' => __('capell-layout-builder::blocks.modern.feature_list.animation_slide'),
                            'zoom' => __('capell-layout-builder::blocks.modern.feature_list.animation_zoom'),
                            'bounce' => __('capell-layout-builder::blocks.modern.feature_list.animation_bounce'),
                        ])
                        ->default('fade-in')
                        ->helperText(__('capell-layout-builder::blocks.modern.feature_list.animation_helper')),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_display'))
                ->description(__('capell-layout-builder::blocks.common.section_display_description'))
                ->schema([
                    Toggle::make('data.customizable')
                        ->label(__('capell-layout-builder::blocks.common.admin_hints_label'))
                        ->default(true)
                        ->helperText(__('capell-layout-builder::blocks.common.customize_message_helper')),
                ])->columns(1),
        ];
    }

    public static function getDefaults(): array
    {
        return [
            'title' => 'Why Choose Our Platform',
            'layout' => 'grid',
            'columns' => '3',
            'animation' => 'fade-in',
            'customizable' => true,
        ];
    }
}
