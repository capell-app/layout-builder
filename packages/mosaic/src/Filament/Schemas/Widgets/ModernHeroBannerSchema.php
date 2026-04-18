<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Widgets;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/**
 * Filament Schema for Modern Hero Banner Widget
 *
 * Provides admin panel form to customize hero banner content and styling
 * without requiring technical knowledge.
 */
class ModernHeroBannerSchema
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Hero heading, subheading, and call-to-action buttons')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Hero Title')
                        ->placeholder('Welcome to Capell')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Main heading displayed on hero banner'),

                    Textarea::make('data.subtitle')
                        ->label('Subtitle / Description')
                        ->placeholder('Create beautiful layouts without code')
                        ->rows(2)
                        ->helperText('Secondary text that appears below the title'),

                    Group::make()
                        ->label('Primary Button (CTA)')
                        ->schema([
                            TextInput::make('data.primaryCta.label')
                                ->label('Button Label')
                                ->placeholder('Get Started')
                                ->required()
                                ->maxLength(50),

                            TextInput::make('data.primaryCta.url')
                                ->label('Button URL')
                                ->placeholder('/pages/create')
                                ->required()
                                ->url(),

                            TextInput::make('data.primaryCta.icon')
                                ->label('Button Icon (emoji)')
                                ->placeholder('🚀')
                                ->maxLength(2)
                                ->helperText('Optional emoji or icon to display in button'),
                        ])->columns(3),

                    Group::make()
                        ->label('Secondary Button (Optional)')
                        ->schema([
                            TextInput::make('data.secondaryCta.label')
                                ->label('Button Label')
                                ->placeholder('Learn More'),

                            TextInput::make('data.secondaryCta.url')
                                ->label('Button URL')
                                ->placeholder('/docs')
                                ->url(),
                        ])->columns(2),
                ])->columns(2),

            Section::make('Styling')
                ->description('Customize colors, layout, and visual appearance')
                ->schema([
                    Select::make('data.height')
                        ->label('Height Preset')
                        ->options([
                            'sm' => 'Small (300px)',
                            'md' => 'Medium (400px)',
                            'lg' => 'Large (500px)',
                            'xl' => 'Extra Large (600px)',
                        ])
                        ->default('lg')
                        ->helperText('Hero section height on desktop'),

                    Select::make('data.textAlign')
                        ->label('Text Alignment')
                        ->options([
                            'left' => 'Left',
                            'center' => 'Center',
                            'right' => 'Right',
                        ])
                        ->default('center'),

                    Select::make('data.accentColor')
                        ->label('Accent Color')
                        ->options([
                            'primary' => 'Violet (Primary)',
                            'secondary' => 'Indigo (Secondary)',
                            'tertiary' => 'Gold (Tertiary)',
                        ])
                        ->default('tertiary')
                        ->helperText('Color used for buttons and highlights'),

                    TextInput::make('data.backgroundImage')
                        ->label('Background Image URL')
                        ->placeholder('https://example.com/image.jpg')
                        ->url()
                        ->helperText('Optional image to overlay with gradient'),

                    TextInput::make('data.videoUrl')
                        ->label('Video Background URL')
                        ->placeholder('https://example.com/video.mp4')
                        ->url()
                        ->helperText('MP4 video to play in background (overrides image)'),

                    Toggle::make('data.parallax')
                        ->label('Enable Parallax Scroll')
                        ->default(false)
                        ->helperText('Creates depth effect as user scrolls'),
                ])->columns(2),

            Section::make('Advanced')
                ->description('Gradient customization and display options')
                ->schema([
                    TextInput::make('data.backgroundGradient')
                        ->label('Background Gradient CSS')
                        ->placeholder('linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)')
                        ->helperText('Custom CSS gradient. Overrides accent color.')
                        ->hint('Example: linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)')
                        ->helperText('Leave empty to use accent color gradient'),

                    Toggle::make('data.customizable')
                        ->label('Show Admin Hints')
                        ->default(true)
                        ->helperText('Display "✨ Customize..." message to indicate editable areas'),
                ])->columns(1),
        ];
    }

    /**
     * Get component data with defaults
     */
    public static function getDefaults(): array
    {
        return [
            'title' => 'Welcome to Capell',
            'subtitle' => 'Create beautiful layouts without code',
            'primaryCta' => [
                'label' => 'Get Started',
                'url' => '#',
                'icon' => '🚀',
            ],
            'secondaryCta' => null,
            'backgroundImage' => null,
            'videoUrl' => null,
            'backgroundGradient' => 'linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)',
            'height' => 'lg',
            'textAlign' => 'center',
            'accentColor' => 'tertiary',
            'parallax' => false,
            'customizable' => true,
        ];
    }
}
