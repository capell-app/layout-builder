<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Image Gallery Widget
 *
 * Provides admin panel controls for customizing image gallery layout,
 * columns, and display options.
 */
class ModernImageGalleryConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Gallery section title and subtitle')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Our Work')
                        ->columnSpanFull(),

                    TextInput::make('data.subtitle')
                        ->label('Subtitle / Description')
                        ->placeholder('Showcasing our latest projects')
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make('Layout')
                ->description('Customize grid layout and display')
                ->schema([
                    Select::make('data.columns')
                        ->label('Grid Columns')
                        ->options([
                            '2' => '2 Columns',
                            '3' => '3 Columns',
                            '4' => '4 Columns',
                        ])
                        ->default('3')
                        ->helperText('Number of images per row (desktop)'),

                    Select::make('data.layout')
                        ->label('Layout Type')
                        ->options([
                            'grid' => 'Grid Layout',
                            'masonry' => 'Masonry Layout',
                        ])
                        ->default('grid')
                        ->helperText('How images are arranged'),
                ])->columns(2),

            Section::make('Display')
                ->description('Visibility and admin hints')
                ->schema([
                    Toggle::make('data.customizable')
                        ->label('Show Admin Hints')
                        ->default(true)
                        ->helperText('Display "✨ Customize..." message'),
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
