<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Testimonials Block
 *
 * Provides admin panel controls for customizing testimonials grid
 * layout and display options.
 */
class ModernTestimonialsConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.testimonials.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.testimonials.title_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::blocks.common.section_layout'))
                ->description(__('capell-layout-builder::blocks.modern.testimonials.section_layout_description'))
                ->schema([
                    Select::make('data.displayMode')
                        ->label(__('capell-layout-builder::blocks.modern.testimonials.display_mode_label'))
                        ->options([
                            'grid' => __('capell-layout-builder::blocks.modern.testimonials.display_mode_grid'),
                            'carousel' => __('capell-layout-builder::blocks.modern.testimonials.display_mode_carousel'),
                        ])
                        ->default('grid')
                        ->helperText(__('capell-layout-builder::blocks.modern.testimonials.display_mode_helper')),

                    Select::make('data.columns')
                        ->label(__('capell-layout-builder::blocks.modern.testimonials.columns_label'))
                        ->options([
                            '1' => __('capell-layout-builder::blocks.modern.testimonials.columns_1'),
                            '2' => __('capell-layout-builder::blocks.common.columns_2'),
                            '3' => __('capell-layout-builder::blocks.common.columns_3'),
                        ])
                        ->default('2')
                        ->helperText(__('capell-layout-builder::blocks.modern.testimonials.columns_helper'))
                        ->visible(fn (callable $get): bool => $get('data.displayMode') === 'grid'),
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
            'title' => 'What Customers Say',
            'displayMode' => 'grid',
            'columns' => '2',
            'customizable' => true,
        ];
    }
}
