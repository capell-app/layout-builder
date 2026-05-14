<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Process Steps Widget
 *
 * Provides admin panel controls for customizing process steps display
 * with title, layout, and customization options.
 */
class ModernProcessStepsConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Process section title and subtitle')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Our Process')
                        ->columnSpanFull(),

                    TextInput::make('data.subtitle')
                        ->label('Subtitle / Description')
                        ->placeholder('Four simple steps to get started')
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make('Layout')
                ->description('Customize steps display layout')
                ->schema([
                    Select::make('data.layout')
                        ->label('Layout Type')
                        ->options([
                            'horizontal' => 'Horizontal (Timeline)',
                            'vertical' => 'Vertical (Stacked)',
                        ])
                        ->default('horizontal')
                        ->helperText('How process steps are arranged'),
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
            'title' => 'Our Process',
            'subtitle' => 'Four simple steps to get started',
            'layout' => 'horizontal',
            'customizable' => true,
        ];
    }
}
