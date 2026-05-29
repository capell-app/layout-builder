<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.cta_section.section_content_description'))
                ->schema([
                    TextInput::make('data.heading')
                        ->label(__('capell-layout-builder::blocks.modern.cta_section.heading_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.heading_placeholder'))
                        ->required()
                        ->maxLength(100)
                        ->columnSpanFull(),

                    Textarea::make('data.subheading')
                        ->label(__('capell-layout-builder::blocks.modern.cta_section.subheading_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.subheading_placeholder'))
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),

                    Group::make()
                        ->schema([
                            TextInput::make('data.primaryButton.label')
                                ->label(__('capell-layout-builder::blocks.common.button_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.primary_button_label_placeholder'))
                                ->required()
                                ->maxLength(50),

                            TextInput::make('data.primaryButton.url')
                                ->label(__('capell-layout-builder::blocks.common.button_url'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.primary_button_url_placeholder'))
                                ->required()
                                ->url(),

                            TextInput::make('data.primaryButton.icon')
                                ->label(__('capell-layout-builder::blocks.common.button_icon'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.primary_button_icon_placeholder'))
                                ->maxLength(2),
                        ])->columns(3)->columnSpanFull(),

                    Group::make()
                        ->schema([
                            TextInput::make('data.secondaryButton.label')
                                ->label(__('capell-layout-builder::blocks.common.button_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.secondary_button_label_placeholder')),

                            TextInput::make('data.secondaryButton.url')
                                ->label(__('capell-layout-builder::blocks.common.button_url'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.cta_section.secondary_button_url_placeholder'))
                                ->url(),
                        ])->columns(2)->columnSpanFull(),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_layout_styling'))
                ->description(__('capell-layout-builder::blocks.common.section_layout_styling_description'))
                ->schema([
                    Select::make('data.layout')
                        ->label(__('capell-layout-builder::blocks.modern.cta_section.layout_label'))
                        ->options([
                            'centered' => __('capell-layout-builder::blocks.modern.cta_section.layout_centered'),
                            'split' => __('capell-layout-builder::blocks.modern.cta_section.layout_split'),
                        ])
                        ->default('centered')
                        ->helperText(__('capell-layout-builder::blocks.modern.cta_section.layout_helper')),

                    Select::make('data.accentColor')
                        ->label(__('capell-layout-builder::blocks.common.accent_color_label'))
                        ->options([
                            'primary' => __('capell-layout-builder::blocks.common.accent_violet'),
                            'secondary' => __('capell-layout-builder::blocks.common.accent_indigo'),
                            'tertiary' => __('capell-layout-builder::blocks.common.accent_gold'),
                        ])
                        ->default('tertiary'),

                    TextInput::make('data.backgroundGradient')
                        ->label(__('capell-layout-builder::blocks.common.background_gradient_label'))
                        ->placeholder(__('capell-layout-builder::blocks.common.background_gradient_placeholder'))
                        ->helperText(__('capell-layout-builder::blocks.common.background_gradient_helper'))
                        ->columnSpanFull(),
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

    /**
     * @return array<array-key, mixed>
     */
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
