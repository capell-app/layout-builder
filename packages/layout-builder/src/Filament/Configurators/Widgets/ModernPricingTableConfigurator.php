<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Pricing Table Widget
 *
 * Provides admin panel controls for customizing pricing table
 * content and display options.
 */
class ModernPricingTableConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make('Content')
                ->description('Pricing section title, currency, and billing options')
                ->schema([
                    TextInput::make('data.title')
                        ->label('Section Title')
                        ->placeholder('Simple, Transparent Pricing')
                        ->columnSpanFull(),

                    TextInput::make('data.currency')
                        ->label('Currency Symbol')
                        ->placeholder('$')
                        ->maxLength(5)
                        ->default('$'),

                    Select::make('data.billingOptions')
                        ->label('Billing Cycle Options')
                        ->options([
                            'monthly' => 'Monthly only',
                            'annual' => 'Annual only',
                            'both' => 'Monthly & Annual (with toggle)',
                        ])
                        ->default('monthly')
                        ->helperText('Show toggle for monthly/annual pricing'),
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
            'title' => 'Simple, Transparent Pricing',
            'currency' => '$',
            'billingOptions' => 'monthly',
            'customizable' => true,
        ];
    }
}
