<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Team Members Widget
 *
 * Provides admin panel controls for customizing team member grid
 * layout and display options.
 */
class ModernTeamMembersConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Team section title')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Our Team')
                        ->columnSpanFull(),
                ])->columns(1),

            Section::make('Layout')
                ->description('Customize grid columns and responsive behavior')
                ->schema([
                    Select::make('data.columns')
                        ->label('Grid Columns')
                        ->options([
                            '2' => '2 Columns',
                            '3' => '3 Columns',
                            '4' => '4 Columns',
                        ])
                        ->default('3')
                        ->helperText('Number of team members per row (desktop)'),
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
            'title' => 'Our Team',
            'columns' => '3',
            'customizable' => true,
        ];
    }
}
