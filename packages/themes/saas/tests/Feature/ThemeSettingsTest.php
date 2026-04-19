<?php

declare(strict_types=1);

use Capell\Themes\Saas\Data\SaasThemeSettings;

test('saas theme settings defaults match design system', function () {
    $settings = new SaasThemeSettings;

    expect($settings->primary_color)->toBe('#6366f1')
        ->and($settings->accent_color)->toBe('#10b981')
        ->and($settings->headline_font)->toBe('inter')
        ->and($settings->body_font)->toBe('inter')
        ->and($settings->hero_style)->toBe('gradient')
        ->and($settings->show_pricing)->toBeTrue()
        ->and($settings->show_integrations)->toBeTrue()
        ->and($settings->show_faq)->toBeTrue()
        ->and($settings->pricing_cycle_default)->toBe('monthly');
});

test('saas theme settings carry product metadata', function () {
    $settings = new SaasThemeSettings(
        product_name: 'Acme SaaS',
        product_description: 'A powerful widget for teams.',
        product_screenshot_url: 'https://example.com/screenshot.png',
    );

    expect($settings->product_name)->toBe('Acme SaaS')
        ->and($settings->product_description)->toBe('A powerful widget for teams.')
        ->and($settings->product_screenshot_url)->toBe('https://example.com/screenshot.png');
});
