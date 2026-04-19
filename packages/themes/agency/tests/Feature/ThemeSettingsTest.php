<?php

declare(strict_types=1);

use Capell\Themes\Agency\Data\AgencyThemeSettings;

test('agency theme settings defaults match design system', function () {
    $settings = new AgencyThemeSettings;

    expect($settings->primary_color)->toBe('#ff5a7e')
        ->and($settings->accent_color)->toBe('#3b82f6')
        ->and($settings->headline_font)->toBe('sora')
        ->and($settings->body_font)->toBe('inter')
        ->and($settings->spacing_preset)->toBe('spacious')
        ->and($settings->show_portfolio)->toBeTrue()
        ->and($settings->show_awards)->toBeTrue()
        ->and($settings->show_clients)->toBeTrue();
});

test('agency theme settings can carry organization and social metadata', function () {
    $settings = new AgencyThemeSettings(
        organization_name: 'Studio Example',
        organization_logo_url: 'https://example.com/logo.png',
        social_instagram: 'https://instagram.com/studio',
        social_dribbble: 'https://dribbble.com/studio',
    );

    expect($settings->organization_name)->toBe('Studio Example')
        ->and($settings->organization_logo_url)->toBe('https://example.com/logo.png')
        ->and($settings->social_instagram)->toBe('https://instagram.com/studio')
        ->and($settings->social_dribbble)->toBe('https://dribbble.com/studio');
});
