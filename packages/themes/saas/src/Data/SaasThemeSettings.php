<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Data;

use Capell\Themes\Core\Data\ThemeSettings;

/**
 * SaaS theme specific settings. Extends shared ThemeSettings with
 * properties unique to the SaaS theme (product metadata, pricing,
 * integrations, conversion knobs).
 */
class SaasThemeSettings extends ThemeSettings
{
    public function __construct(
        string $active_theme = 'saas',
        string $primary_color = '#6366f1',
        string $accent_color = '#10b981',
        string $headline_font = 'inter',
        string $body_font = 'inter',
        string $hero_style = 'gradient',
        string $footer_layout = 'expanded',
        string $spacing_preset = 'balanced',
        bool $show_testimonials = true,
        bool $show_pricing = true,
        bool $show_blog = false,
        bool $show_contact = true,
        public bool $show_integrations = true,
        public bool $show_use_cases = true,
        public bool $show_faq = true,
        public string $product_name = 'Capell',
        public ?string $product_description = null,
        public ?string $product_screenshot_url = null,
        public string $pricing_cycle_default = 'monthly',
        public ?string $app_store_url = null,
        public ?string $play_store_url = null,
        public ?string $social_twitter = null,
        public ?string $social_linkedin = null,
        public ?string $social_github = null,
        public ?string $social_youtube = null,
    ) {
        parent::__construct(
            active_theme: $active_theme,
            primary_color: $primary_color,
            accent_color: $accent_color,
            headline_font: $headline_font,
            body_font: $body_font,
            hero_style: $hero_style,
            footer_layout: $footer_layout,
            spacing_preset: $spacing_preset,
            show_testimonials: $show_testimonials,
            show_pricing: $show_pricing,
            show_blog: $show_blog,
            show_contact: $show_contact,
        );
    }
}
