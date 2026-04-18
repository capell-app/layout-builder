<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Widgets;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/**
 * Filament Schema for Modern Feature List Widget
 *
 * Provides admin panel controls for customizing feature list layout
 * and display options.
 */
class ModernFeatureListSchema
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Feature list title and layout')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Why Choose Our Platform')
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make('Layout & Display')
                ->description('Customize layout variant, columns, and animations')
                ->schema([
                    Select::make('data.layout')
                        ->label('Layout Type')
                        ->options([
                            'vertical' => 'Vertical (Stacked)',
                            'grid' => 'Grid (Side by side)',
                        ])
                        ->default('grid')
                        ->helperText('How features are arranged'),

                    Select::make('data.columns')
                        ->label('Grid Columns')
                        ->options([
                            '2' => '2 Columns',
                            '3' => '3 Columns',
                            '4' => '4 Columns',
                        ])
                        ->default('3')
                        ->visible(fn (callable $get) => $get('data.layout') === 'grid'),

                    Select::make('data.animation')
                        ->label('Entry Animation')
                        ->options([
                            'fade-in' => 'Fade In',
                            'slide-up' => 'Slide Up',
                            'zoom' => 'Zoom',
                            'bounce' => 'Bounce In',
                        ])
                        ->default('fade-in')
                        ->helperText('Animation effect when features appear'),
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
            'title' => 'Why Choose Our Platform',
            'layout' => 'grid',
            'columns' => '3',
            'animation' => 'fade-in',
            'customizable' => true,
        ];
    }
}
