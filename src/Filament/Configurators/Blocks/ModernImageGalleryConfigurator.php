<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Image Gallery Block
 *
 * Provides admin panel controls for customizing image gallery layout,
 * columns, and display options.
 */
class ModernImageGalleryConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.image_gallery.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.image_gallery.title_placeholder'))
                        ->columnSpanFull(),

                    TextInput::make('data.subtitle')
                        ->label(__('capell-layout-builder::blocks.common.subtitle_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.image_gallery.subtitle_placeholder'))
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make(__('capell-layout-builder::blocks.common.section_layout'))
                ->description(__('capell-layout-builder::blocks.modern.image_gallery.section_layout_description'))
                ->schema([
                    Select::make('data.columns')
                        ->label(__('capell-layout-builder::blocks.common.grid_columns_label'))
                        ->options([
                            '2' => __('capell-layout-builder::blocks.common.columns_2'),
                            '3' => __('capell-layout-builder::blocks.common.columns_3'),
                            '4' => __('capell-layout-builder::blocks.common.columns_4'),
                        ])
                        ->default('3')
                        ->helperText(__('capell-layout-builder::blocks.common.grid_columns_helper')),

                    Select::make('data.layout')
                        ->label(__('capell-layout-builder::blocks.modern.image_gallery.layout_label'))
                        ->options([
                            'grid' => __('capell-layout-builder::blocks.modern.image_gallery.layout_grid'),
                            'masonry' => __('capell-layout-builder::blocks.modern.image_gallery.layout_masonry'),
                        ])
                        ->default('grid')
                        ->helperText(__('capell-layout-builder::blocks.modern.image_gallery.layout_helper')),
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
            'title' => 'Our Work',
            'subtitle' => 'Showcasing our latest projects',
            'columns' => '3',
            'layout' => 'grid',
            'customizable' => true,
        ];
    }
}
