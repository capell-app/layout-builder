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
 * Filament Schema for Modern Hero Banner Widget
 *
 * Provides admin panel form to customize hero banner content and styling
 * without requiring technical knowledge.
 */
class ModernHeroBannerConfigurator
{
    /**
     * @return array<array-key, mixed>
     */
    public static function getFormSchema(): array
    {
        return [
            Section::make(__('capell-layout-builder::blocks.common.section_content'))
                ->description(__('capell-layout-builder::blocks.modern.hero_banner.section_content_description'))
                ->schema([
                    TextInput::make('data.title')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.title_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.title_placeholder'))
                        ->required()
                        ->maxLength(100)
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.title_helper')),

                    Textarea::make('data.subtitle')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.subtitle_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.subtitle_placeholder'))
                        ->rows(2)
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.subtitle_helper')),

                    Group::make()
                        ->schema([
                            TextInput::make('data.primaryCta.label')
                                ->label(__('capell-layout-builder::blocks.common.button_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.primary_button_label_placeholder'))
                                ->required()
                                ->maxLength(50),

                            TextInput::make('data.primaryCta.url')
                                ->label(__('capell-layout-builder::blocks.common.button_url'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.primary_button_url_placeholder'))
                                ->required()
                                ->url(),

                            TextInput::make('data.primaryCta.icon')
                                ->label(__('capell-layout-builder::blocks.common.button_icon'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.primary_button_icon_placeholder'))
                                ->maxLength(2)
                                ->helperText(__('capell-layout-builder::blocks.common.button_icon_helper')),
                        ])->columns(3),

                    Group::make()
                        ->schema([
                            TextInput::make('data.secondaryCta.label')
                                ->label(__('capell-layout-builder::blocks.common.button_label'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.secondary_button_label_placeholder')),

                            TextInput::make('data.secondaryCta.url')
                                ->label(__('capell-layout-builder::blocks.common.button_url'))
                                ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.secondary_button_url_placeholder'))
                                ->url(),
                        ])->columns(2),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_styling'))
                ->description(__('capell-layout-builder::blocks.common.section_styling_description'))
                ->schema([
                    Select::make('data.height')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.height_label'))
                        ->options([
                            'sm' => __('capell-layout-builder::blocks.modern.hero_banner.height_sm'),
                            'md' => __('capell-layout-builder::blocks.modern.hero_banner.height_md'),
                            'lg' => __('capell-layout-builder::blocks.modern.hero_banner.height_lg'),
                            'xl' => __('capell-layout-builder::blocks.modern.hero_banner.height_xl'),
                        ])
                        ->default('lg')
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.height_helper')),

                    Select::make('data.textAlign')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.text_align_label'))
                        ->options([
                            'left' => __('capell-layout-builder::blocks.modern.hero_banner.align_left'),
                            'center' => __('capell-layout-builder::blocks.modern.hero_banner.align_center'),
                            'right' => __('capell-layout-builder::blocks.modern.hero_banner.align_right'),
                        ])
                        ->default('center'),

                    Select::make('data.accentColor')
                        ->label(__('capell-layout-builder::blocks.common.accent_color_label'))
                        ->options([
                            'primary' => __('capell-layout-builder::blocks.modern.hero_banner.accent_primary'),
                            'secondary' => __('capell-layout-builder::blocks.modern.hero_banner.accent_secondary'),
                            'tertiary' => __('capell-layout-builder::blocks.modern.hero_banner.accent_tertiary'),
                        ])
                        ->default('tertiary')
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.accent_helper')),

                    TextInput::make('data.backgroundImage')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.background_image_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.background_image_placeholder'))
                        ->url()
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.background_image_helper')),

                    TextInput::make('data.videoUrl')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.video_url_label'))
                        ->placeholder(__('capell-layout-builder::blocks.modern.hero_banner.video_url_placeholder'))
                        ->url()
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.video_url_helper')),

                    Toggle::make('data.parallax')
                        ->label(__('capell-layout-builder::blocks.modern.hero_banner.parallax_label'))
                        ->default(false)
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.parallax_helper')),
                ])->columns(2),

            Section::make(__('capell-layout-builder::blocks.common.section_advanced'))
                ->description(__('capell-layout-builder::blocks.common.section_advanced_description'))
                ->schema([
                    TextInput::make('data.backgroundGradient')
                        ->label(__('capell-layout-builder::blocks.common.background_gradient_label'))
                        ->placeholder(__('capell-layout-builder::blocks.common.background_gradient_placeholder'))
                        ->helperText(__('capell-layout-builder::blocks.modern.hero_banner.background_gradient_helper'))
                        ->hint(__('capell-layout-builder::blocks.modern.hero_banner.background_gradient_hint')),

                    Toggle::make('data.customizable')
                        ->label(__('capell-layout-builder::blocks.common.admin_hints_label'))
                        ->default(true)
                        ->helperText(__('capell-layout-builder::blocks.common.admin_hints_helper')),
                ])->columns(1),
        ];
    }

    /**
     * Get component data with defaults
     *
     * @return array<array-key, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'title' => 'Welcome to Capell',
            'subtitle' => 'Create beautiful layouts without code',
            'primaryCta' => [
                'label' => 'Get Started',
                'url' => '#',
                'icon' => '🚀',
            ],
            'secondaryCta' => null,
            'backgroundImage' => null,
            'videoUrl' => null,
            'backgroundGradient' => 'linear-gradient(135deg, #7c3aed 0%, #3131c0 100%)',
            'height' => 'lg',
            'textAlign' => 'center',
            'accentColor' => 'tertiary',
            'parallax' => false,
            'customizable' => true,
        ];
    }
}
