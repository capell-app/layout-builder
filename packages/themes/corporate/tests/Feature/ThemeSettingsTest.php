<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Data\CorporateThemeSettings;

test('corporate theme settings defaults match design system', function () {
    $settings = new CorporateThemeSettings;

    expect($settings->primary_color)->toBe('#1a2d6d')
        ->and($settings->accent_color)->toBe('#f59e0b')
        ->and($settings->headline_font)->toBe('playfair')
        ->and($settings->body_font)->toBe('inter')
        ->and($settings->footer_layout)->toBe('expanded')
        ->and($settings->show_case_studies)->toBeTrue()
        ->and($settings->show_team)->toBeTrue();
});

test('corporate theme settings can carry organization metadata', function () {
    $settings = new CorporateThemeSettings(
        organization_name: 'Acme',
        organization_logo_url: 'https://example.com/logo.png',
    );

    expect($settings->organization_name)->toBe('Acme')
        ->and($settings->organization_logo_url)->toBe('https://example.com/logo.png');
});
