<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Testimonials Widget
 *
 * Provides admin panel controls for customizing testimonials grid
 * layout and display options.
 */
class ModernTestimonialsSchema
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Testimonials section title')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('What Customers Say')
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make('Layout')
                ->description('Customize display mode and responsive behavior')
                ->schema([
                    Select::make('data.displayMode')
                        ->label('Display Mode')
                        ->options([
                            'grid' => 'Grid (Multiple columns)',
                            'carousel' => 'Carousel (Slider with navigation)',
                        ])
                        ->default('grid')
                        ->helperText('How to display testimonials'),

                    Select::make('data.columns')
                        ->label('Grid Columns')
                        ->options([
                            '1' => '1 Column (Full width)',
                            '2' => '2 Columns',
                            '3' => '3 Columns',
                        ])
                        ->default('2')
                        ->helperText('Number of testimonials per row (desktop)')
                        ->visible(fn (callable $get): bool => $get('data.displayMode') === 'grid'),
                ])->columns(1),

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
            'title' => 'What Customers Say',
            'displayMode' => 'grid',
            'columns' => '2',
            'customizable' => true,
        ];
    }
}
