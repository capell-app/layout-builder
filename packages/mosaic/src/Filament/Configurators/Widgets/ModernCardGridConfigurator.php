<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

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
    public static function getFormSchema(): array
    {
        return [
            Section::make('Header')
                ->description('Section title and description displayed above cards')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Featured Widgets')
                        ->maxLength(100),

                    Textarea::make('data.description')
                        ->label('Section Description')
                        ->placeholder('Choose from our collection of modern, customizable components')
                        ->rows(2),
                ])->columns(2),

            Section::make('Cards')
                ->description('Add and customize individual cards')
                ->schema([
                    Repeater::make('data.cards')
                        ->label('Cards')
                        ->schema([
                            TextInput::make('icon')
                                ->label('Icon (emoji)')
                                ->placeholder('🎨')
                                ->maxLength(2)
                                ->helperText('Single emoji or icon'),

                            TextInput::make('title')
                                ->label('Card Title')
                                ->placeholder('Design System')
                                ->required()
                                ->maxLength(50),

                            Textarea::make('description')
                                ->label('Card Description')
                                ->placeholder('Modern tokens and components')
                                ->rows(2)
                                ->maxLength(200),

                            TextInput::make('image')
                                ->label('Card Image URL')
                                ->placeholder('https://example.com/image.jpg')
                                ->url()
                                ->helperText('Optional image displayed at top of card'),

                            TextInput::make('badge')
                                ->label('Badge Label')
                                ->placeholder('Popular')
                                ->maxLength(30)
                                ->helperText('Optional badge displayed in top-right corner'),

                            TextInput::make('link.label')
                                ->label('Link Text')
                                ->placeholder('Learn More')
                                ->maxLength(30),

                            TextInput::make('link.url')
                                ->label('Link URL')
                                ->placeholder('/docs')
                                ->url(),
                        ])
                        ->columns(2)
                        ->defaultItems(3)
                        ->minItems(1)
                        ->maxItems(12)
                        ->addActionLabel('Add Card')
                        ->deleteActionLabel('Remove'),
                ])->columns(1),

            Section::make('Layout')
                ->description('Grid columns and visual variant')
                ->schema([
                    Select::make('data.columns')
                        ->label('Number of Columns')
                        ->options([
                            2 => '2 Columns',
                            3 => '3 Columns',
                            4 => '4 Columns',
                        ])
                        ->default(3)
                        ->helperText('Adapts responsively on mobile (1 column)'),

                    Select::make('data.variant')
                        ->label('Card Variant')
                        ->options([
                            'default' => 'Default (Subtle)',
                            'elevated' => 'Elevated (With Shadow)',
                            'glass' => 'Glassmorphism (Frosted)',
                        ])
                        ->default('default')
                        ->helperText('Visual style of individual cards'),

                    Select::make('data.accentColor')
                        ->label('Link Accent Color')
                        ->options([
                            'primary' => 'Violet',
                            'secondary' => 'Indigo',
                            'tertiary' => 'Gold',
                        ])
                        ->default('primary')
                        ->helperText('Color of "Learn More" links'),

                    Select::make('data.hoverEffect')
                        ->label('Hover Effect')
                        ->options([
                            'scale' => 'Scale (Zoom 5%)',
                            'shadow' => 'Shadow (Enhanced shadow)',
                            'lift' => 'Lift (Raise with shadow)',
                        ])
                        ->default('scale')
                        ->helperText('Animation effect when hovering over cards'),
                ])->columns(2),

            Section::make('Display')
                ->description('Visibility and admin hints')
                ->schema([
                    Toggle::make('data.customizable')
                        ->label('Show Admin Hints')
                        ->default(true),
                ])->columns(1),
        ];
    }

    public static function getDefaults(): array
    {
        return [
            'title' => 'Featured Widgets',
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
