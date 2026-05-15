<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Elements;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Stats Section Element
 *
 * Provides admin panel controls for customizing statistics display
 * with title, subtitle, layout, and icon options.
 */
class ModernStatsSectionConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Statistics section title and subtitle')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('By The Numbers')
                        ->columnSpanFull(),

                    TextInput::make('data.subtitle')
                        ->label('Subtitle / Description')
                        ->placeholder('Proven results that speak for themselves')
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make('Layout')
                ->description('Customize statistics layout')
                ->schema([
                    Select::make('data.layout')
                        ->label('Layout Type')
                        ->options([
                            'horizontal' => 'Horizontal (4 columns)',
                            'vertical' => 'Vertical (Single column)',
                        ])
                        ->default('horizontal')
                        ->helperText('How statistics are arranged'),
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
            'title' => 'By The Numbers',
            'subtitle' => 'Proven results that speak for themselves',
            'layout' => 'horizontal',
            'customizable' => true,
        ];
    }
}
