<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern Pricing Table Block
 *
 * Provides admin panel controls for customizing pricing table
 * content and display options.
 */
class ModernPricingTableConfigurator
{
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.pricing_table.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.pricing_table.title_placeholder'))
                        ->columnSpanFull(),

                    TextInput::make('data.currency')
                        ->label(__('capell-layout-builder::blocks.modern.pricing_table.currency_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.pricing_table.currency_placeholder'))
                        ->maxLength(5)
                        ->default('$'),

                    Select::make('data.billingOptions')
                        ->label(__('capell-layout-builder::blocks.modern.pricing_table.billing_label'))
                        ->options([
                            'monthly' => __('capell-layout-builder::blocks.modern.pricing_table.billing_monthly'),
                            'annual' => __('capell-layout-builder::blocks.modern.pricing_table.billing_annual'),
                            'both' => __('capell-layout-builder::blocks.modern.pricing_table.billing_both'),
                        ])
                        ->default('monthly')
                        ->helperText(__('capell-layout-builder::blocks.modern.pricing_table.billing_helper')),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_display'))
                ->description(__('capell-layout-builder::blocks.common.section_display_description'))
                ->schema([
                    Toggle::make('data.customizable')
                        ->label(__('capell-layout-builder::blocks.common.admin_hints_label'))
                        ->default(true)
                        ->helperText(__('capell-layout-builder::blocks.common.customize_message_helper')),
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
