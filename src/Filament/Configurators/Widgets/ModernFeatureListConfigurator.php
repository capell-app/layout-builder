<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Enums\ModernFeatureListAnimation;
use Capell\LayoutBuilder\Enums\ModernFeatureListLayout;
use Capell\LayoutBuilder\Enums\ModernGridColumnCount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Feature List Widget
 *
 * Provides admin panel controls for customizing feature list layout
 * and display options.
 */
class ModernFeatureListConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::widgets.common.section_content'))
                ->description(__('capell-layout-builder::widgets.modern.feature_list.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::widgets.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::widgets.modern.feature_list.title_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::widgets.common.section_layout_display'))
                ->description(__('capell-layout-builder::widgets.common.section_layout_display_description'))
                ->schema([
                    Select::make('data.layout')
                        ->label(__('capell-layout-builder::widgets.modern.feature_list.layout_label'))
                        ->options(ModernFeatureListLayout::class)
                        ->default('grid')
                        ->helperText(__('capell-layout-builder::widgets.modern.feature_list.layout_helper')),

                    Select::make('data.columns')
                        ->label(__('capell-layout-builder::widgets.common.grid_columns_label'))
                        ->options(ModernGridColumnCount::class)
                        ->default('3')
                        ->visible(fn (callable $get): bool => $get('data.layout') === 'grid'),

                    Select::make('data.animation')
                        ->label(__('capell-layout-builder::widgets.modern.feature_list.animation_label'))
                        ->options(ModernFeatureListAnimation::class)
                        ->default('fade-in')
                        ->helperText(__('capell-layout-builder::widgets.modern.feature_list.animation_helper')),
                ])->columns(2),

            Section::make(__('capell-layout-builder::widgets.common.section_display'))
                ->description(__('capell-layout-builder::widgets.common.section_display_description'))
                ->schema([
                    Toggle::make('data.customizable')
                        ->label(__('capell-layout-builder::widgets.common.admin_hints_label'))
                        ->default(true)
                        ->helperText(__('capell-layout-builder::widgets.common.customize_message_helper')),
                ])->columns(1),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
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
