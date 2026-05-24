<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Filament Schema for Modern FAQ Section Widget
 *
 * Provides admin panel controls for customizing FAQ accordion
 * content and display options.
 */
class ModernFaqConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.faq.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.common.section_title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.faq.title_placeholder'))
                        ->columnSpanFull(),

                    Repeater::make('data.categories')
                        ->label(__('capell-layout-builder::blocks.modern.faq.categories_label'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('capell-layout-builder::blocks.modern.faq.category_name_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.faq.category_name_placeholder'))
                                ->required()
                                ->maxLength(50),
                        ])
                        ->columns(1)
                        ->defaultItems(3)
                        ->minItems(0)
                        ->maxItems(10)
                        ->addActionLabel(__('capell-layout-builder::blocks.modern.faq.add_category')),
                ])->columns(1),

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

    /**
     * @return array<array-key, mixed>
     */
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
