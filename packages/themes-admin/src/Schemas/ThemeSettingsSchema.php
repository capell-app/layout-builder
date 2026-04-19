<?php

declare(strict_types=1);

namespace Capell\Themes\Admin\Schemas;

use Capell\Themes\Core\Theme\ThemeRegistrar;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class ThemeSettingsSchema
{
    public static function make(): Tabs
    {
        return Tabs::make('Theme Settings')
            ->tabs([
                Tab::make('Theme')
                    ->schema([
                        Select::make('active_theme')
                            ->label('Active Theme')
                            ->options(ThemeRegistrar::options())
                            ->required(),
                    ]),
                Tab::make('Colors')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->required(),
                        ColorPicker::make('accent_color')
                            ->label('Accent Color')
                            ->required(),
                    ]),
                Tab::make('Typography')
                    ->schema([
                        Select::make('headline_font')
                            ->label('Headline Font')
                            ->options([
                                'playfair' => 'Playfair Display',
                                'sora' => 'Sora',
                                'inter' => 'Inter',
                            ])
                            ->required(),
                        Select::make('body_font')
                            ->label('Body Font')
                            ->options([
                                'inter' => 'Inter',
                                'manrope' => 'Manrope',
                            ])
                            ->required(),
                    ]),
                Tab::make('Layout')
                    ->schema([
                        Select::make('hero_style')
                            ->label('Hero Background')
                            ->options([
                                'image' => 'Image',
                                'gradient' => 'Gradient',
                                'video' => 'Video',
                            ])
                            ->required(),
                        Select::make('footer_layout')
                            ->label('Footer Layout')
                            ->options([
                                'minimal' => 'Minimal',
                                'expanded' => 'Expanded',
                                'newsletter' => 'Newsletter',
                            ])
                            ->required(),
                        Select::make('spacing_preset')
                            ->label('Spacing')
                            ->options([
                                'compact' => 'Compact',
                                'balanced' => 'Balanced',
                                'spacious' => 'Spacious',
                            ])
                            ->required(),
                    ]),
                Tab::make('Sections')
                    ->schema([
                        Toggle::make('show_testimonials')
                            ->label('Show Testimonials Section'),
                        Toggle::make('show_pricing')
                            ->label('Show Pricing Section'),
                        Toggle::make('show_blog')
                            ->label('Show Blog Section'),
                        Toggle::make('show_contact')
                            ->label('Show Contact Section'),
                    ]),
            ]);
    }
}
