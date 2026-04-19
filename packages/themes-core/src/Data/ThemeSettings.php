<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Data;

use Spatie\LaravelData\Data;

class ThemeSettings extends Data
{
    public function __construct(
        public string $active_theme,
        public string $primary_color = '#1a2d6d',
        public string $accent_color = '#f59e0b',
        public string $headline_font = 'playfair',
        public string $body_font = 'inter',
        public string $hero_style = 'image',
        public string $footer_layout = 'expanded',
        public string $spacing_preset = 'balanced',
        public bool $show_testimonials = true,
        public bool $show_pricing = false,
        public bool $show_blog = true,
        public bool $show_contact = true,
    ) {}
}
