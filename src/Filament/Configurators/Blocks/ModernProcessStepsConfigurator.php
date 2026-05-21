<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Process Steps Block
 *
 * Provides admin panel controls for customizing process steps display
 * with title, layout, and customization options.
 */
class ModernProcessStepsConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.process_steps.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.process_steps.title_placeholder'))
                        ->columnSpanFull(),

                    TextInput::make('data.subtitle')
                        ->label(__('capell-layout-builder::blocks.common.subtitle_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.process_steps.subtitle_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::blocks.common.section_layout'))
                ->description(__('capell-layout-builder::blocks.modern.process_steps.section_layout_description'))
                ->schema([
                    Select::make('data.layout')
                        ->label(__('capell-layout-builder::blocks.modern.process_steps.layout_label'))
                        ->options([
                            'horizontal' => __('capell-layout-builder::blocks.modern.process_steps.layout_horizontal'),
                            'vertical' => __('capell-layout-builder::blocks.modern.process_steps.layout_vertical'),
                        ])
                        ->default('horizontal')
                        ->helperText(__('capell-layout-builder::blocks.modern.process_steps.layout_helper')),
                ])->columns(1),

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
            'title' => 'Our Process',
            'subtitle' => 'Four simple steps to get started',
            'layout' => 'horizontal',
            'customizable' => true,
        ];
    }
}
