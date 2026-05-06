<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern CTA Section Widget
 *
 * Provides admin panel controls for customizing call-to-action section
 * content, buttons, layout, and styling.
 */
class ModernCTASectionConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Heading, subheading, and call-to-action buttons')
                ->schema([
                    TextInput::make('data.heading')
                        ->label('Main Heading')
                        ->placeholder('Ready to Create Stunning Layouts?')
                        ->required()
                        ->maxLength(100)
                        ->columnSpanFull(),

                    Textarea::make('data.subheading')
                        ->label('Subheading / Description')
                        ->placeholder('No coding required. Drag, drop, customize, and publish.')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),

                    Group::make()
                        ->label('Primary Button (CTA)')
                        ->schema([
                            TextInput::make('data.primaryButton.label')
                                ->label('Button Label')
                                ->placeholder('Start Building')
                                ->required()
                                ->maxLength(50),

                            TextInput::make('data.primaryButton.url')
                                ->label('Button URL')
                                ->placeholder('#signup')
                                ->required()
                                ->url(),

                            TextInput::make('data.primaryButton.icon')
                                ->label('Button Icon (emoji)')
                                ->placeholder('🚀')
                                ->maxLength(2),
                        ])->columns(3)->columnSpanFull(),

                    Group::make()
                        ->label('Secondary Button (Optional)')
                        ->schema([
                            TextInput::make('data.secondaryButton.label')
                                ->label('Button Label')
                                ->placeholder('View Docs'),

                            TextInput::make('data.secondaryButton.url')
                                ->label('Button URL')
                                ->placeholder('/docs')
                                ->url(),
                        ])->columns(2)->columnSpanFull(),
                ])->columns(2),

            Section::make('Layout & Styling')
                ->description('Customize layout variant and background')
                ->schema([
                    Select::make('data.layout')
                        ->label('Layout')
                        ->options([
                            'centered' => 'Centered (Text + buttons stacked)',
                            'split' => 'Split (Text left, icon right)',
                        ])
                        ->default('centered')
                        ->helperText('How content is arranged'),

                    Select::make('data.accentColor')
                        ->label('Accent Color')
                        ->options([
                            'primary' => 'Violet',
                            'secondary' => 'Indigo',
                            'tertiary' => 'Gold',
                        ])
                        ->default('tertiary'),

                    TextInput::make('data.backgroundGradient')
                        ->label('Background Gradient CSS')
                        ->placeholder('linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)')
                        ->helperText('Custom CSS gradient for section background')
                        ->columnSpanFull(),
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
            'heading' => 'Ready to Create Stunning Layouts?',
            'subheading' => 'No coding required. Drag, drop, customize, and publish.',
            'primaryButton' => [
                'label' => 'Start Building',
                'url' => '#',
                'icon' => '🚀',
            ],
            'secondaryButton' => [
                'label' => 'View Docs',
                'url' => '/docs',
            ],
            'layout' => 'centered',
            'accentColor' => 'tertiary',
            'backgroundGradient' => 'linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)',
            'customizable' => true,
        ];
    }
}
