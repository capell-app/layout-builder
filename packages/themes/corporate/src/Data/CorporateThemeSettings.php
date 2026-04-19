<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Data;

use Capell\Themes\Core\Data\ThemeSettings;

/**
 * Corporate theme specific settings. Extends shared ThemeSettings with
 * properties unique to the Corporate theme (case studies, team, SEO hints).
 */
class CorporateThemeSettings extends ThemeSettings
{
    public function __construct(
        string $active_theme = 'corporate',
        string $primary_color = '#1a2d6d',
        string $accent_color = '#f59e0b',
        string $headline_font = 'playfair',
        string $body_font = 'inter',
        string $hero_style = 'image',
        string $footer_layout = 'expanded',
        string $spacing_preset = 'balanced',
        bool $show_testimonials = true,
        bool $show_pricing = false,
        bool $show_blog = true,
        bool $show_contact = true,
        public bool $show_case_studies = true,
        public bool $show_team = true,
        public string $organization_name = 'Capell',
        public ?string $organization_logo_url = null,
        public ?string $organization_description = null,
        public ?string $social_twitter = null,
        public ?string $social_linkedin = null,
        public ?string $social_github = null,
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
