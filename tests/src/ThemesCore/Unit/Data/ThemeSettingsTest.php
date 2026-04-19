<?php

declare(strict_types=1);

use Capell\Themes\Core\Data\ThemeSettings;

test('creates theme settings with all properties', function (): void {
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

    expect($settings->active_theme)->toBe('corporate');
    expect($settings->primary_color)->toBe('#1a2d6d');
    expect($settings->accent_color)->toBe('#f59e0b');
    expect($settings->headline_font)->toBe('playfair');
    expect($settings->body_font)->toBe('inter');
    expect($settings->hero_style)->toBe('image');
    expect($settings->footer_layout)->toBe('expanded');
    expect($settings->spacing_preset)->toBe('balanced');
    expect($settings->show_testimonials)->toBeTrue();
    expect($settings->show_pricing)->toBeFalse();
    expect($settings->show_blog)->toBeTrue();
    expect($settings->show_contact)->toBeTrue();
});

test('theme settings has sensible defaults', function (): void {
    $settings = new ThemeSettings(active_theme: 'corporate');

    expect($settings->active_theme)->toBe('corporate');
    expect($settings->primary_color)->toBe('#1a2d6d');
    expect($settings->headline_font)->toBe('playfair');
    expect($settings->show_blog)->toBeTrue();
});
