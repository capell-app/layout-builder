<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\FormBuilder\Components\Repeater;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern FAQ Section Widget
 *
 * Provides admin panel controls for customizing FAQ accordion
 * content and display options.
 */
class ModernFaqConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('FAQ section title and categories')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Frequently Asked Questions')
                        ->columnSpanFull(),

                    Repeater::make('data.categories')
                        ->label('Categories')
                        ->schema([
                            TextInput::make('name')
                                ->label('Category Name')
                                ->placeholder('Getting Started')
                                ->required()
                                ->maxLength(50),
                        ])
                        ->columns(1)
                        ->defaultItems(3)
                        ->minItems(0)
                        ->maxItems(10)
                        ->addActionLabel('Add Category')
                        ->deleteActionLabel('Remove'),
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
            'title' => 'Frequently Asked Questions',
            'categories' => [
                ['name' => 'Getting Started'],
                ['name' => 'Features'],
                ['name' => 'Pricing'],
            ],
            'customizable' => true,
        ];
    }
}
