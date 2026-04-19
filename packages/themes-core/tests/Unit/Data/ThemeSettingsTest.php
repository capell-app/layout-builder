<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Tests\Unit\Data;

use Capell\Themes\Core\Data\ThemeSettings;
use PHPUnit\Framework\TestCase;

class ThemeSettingsTest extends TestCase
{
    public function test_creates_theme_settings_with_all_properties(): void
    {
        $settings = new ThemeSettings(
            active_theme: 'corporate',
            primary_color: '#1a2d6d',
            accent_color: '#f59e0b',
            headline_font: 'playfair',
            body_font: 'inter',
            hero_style: 'image',
            footer_layout: 'expanded',
            spacing_preset: 'balanced',
            show_testimonials: true,
            show_pricing: false,
            show_blog: true,
            show_contact: true,
        );

        $this->assertSame('corporate', $settings->active_theme);
        $this->assertSame('#1a2d6d', $settings->primary_color);
        $this->assertSame('#f59e0b', $settings->accent_color);
        $this->assertSame('playfair', $settings->headline_font);
        $this->assertSame('inter', $settings->body_font);
        $this->assertSame('image', $settings->hero_style);
        $this->assertSame('expanded', $settings->footer_layout);
        $this->assertSame('balanced', $settings->spacing_preset);
        $this->assertTrue($settings->show_testimonials);
        $this->assertFalse($settings->show_pricing);
        $this->assertTrue($settings->show_blog);
        $this->assertTrue($settings->show_contact);
    }

    public function test_theme_settings_has_sensible_defaults(): void
    {
        $settings = ThemeSettings::from([
            'active_theme' => 'corporate',
        ]);

        $this->assertSame('corporate', $settings->active_theme);
        $this->assertSame('#1a2d6d', $settings->primary_color);
        $this->assertSame('playfair', $settings->headline_font);
        $this->assertTrue($settings->show_blog);
    }
}
