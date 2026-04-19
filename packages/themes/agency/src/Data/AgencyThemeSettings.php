<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Data;

use Capell\Themes\Core\Data\ThemeSettings;

/**
 * Agency theme specific settings. Extends shared ThemeSettings with
 * properties unique to the Agency theme (portfolio, awards, social).
 */
class AgencyThemeSettings extends ThemeSettings
{
    public function __construct(
        string $active_theme = 'agency',
        string $primary_color = '#ff5a7e',
        string $accent_color = '#3b82f6',
        string $headline_font = 'sora',
        string $body_font = 'inter',
        string $hero_style = 'gradient',
        string $footer_layout = 'expanded',
        string $spacing_preset = 'spacious',
        bool $show_testimonials = true,
        bool $show_pricing = false,
        bool $show_blog = false,
        bool $show_contact = true,
        public bool $show_portfolio = true,
        public bool $show_awards = true,
        public bool $show_clients = true,
        public string $organization_name = 'Capell',
        public ?string $organization_logo_url = null,
        public ?string $organization_description = null,
        public ?string $social_instagram = null,
        public ?string $social_dribbble = null,
        public ?string $social_behance = null,
        public ?string $social_linkedin = null,
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
