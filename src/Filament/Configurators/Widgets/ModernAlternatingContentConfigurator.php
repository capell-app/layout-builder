<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Configurators\Widgets;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Alternating Content Widget
 *
 * Provides admin panel controls for customizing two-column alternating
 * content layout with images and text.
 */
class ModernAlternatingContentConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Section title and layout')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('How It Works')
                        ->columnSpanFull(),
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
            'title' => 'How It Works',
            'customizable' => true,
        ];
    }
}
