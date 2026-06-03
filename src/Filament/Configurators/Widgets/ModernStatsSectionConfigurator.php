<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Stats Section Widget
 *
 * Provides admin panel controls for customizing statistics display
 * with title, subtitle, layout, and icon options.
 */
class ModernStatsSectionConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::widgets.common.section_content'))
                ->description(__('capell-layout-builder::widgets.modern.stats_section.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::widgets.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::widgets.modern.stats_section.title_placeholder'))
                        ->columnSpanFull(),

                    TextInput::make('data.subtitle')
                        ->label(__('capell-layout-builder::widgets.common.subtitle_label'))
                        ->placeholder(__('capell-layout-builder::widgets.modern.stats_section.subtitle_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::widgets.common.section_layout'))
                ->description(__('capell-layout-builder::widgets.modern.stats_section.section_layout_description'))
                ->schema([
                    Select::make('data.layout')
                        ->label(__('capell-layout-builder::widgets.modern.stats_section.layout_label'))
                        ->options([
                            'horizontal' => __('capell-layout-builder::widgets.modern.stats_section.layout_horizontal'),
                            'vertical' => __('capell-layout-builder::widgets.modern.stats_section.layout_vertical'),
                        ])
                        ->default('horizontal')
                        ->helperText(__('capell-layout-builder::widgets.modern.stats_section.layout_helper')),
                ])->columns(1),

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
            'title' => 'By The Numbers',
            'subtitle' => 'Proven results that speak for themselves',
            'layout' => 'horizontal',
            'customizable' => true,
        ];
    }
}
