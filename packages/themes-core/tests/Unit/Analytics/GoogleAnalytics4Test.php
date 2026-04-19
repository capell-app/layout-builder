<?php

declare(strict_types=1);

use Capell\Themes\Core\Analytics\GoogleAnalytics4;

test('renders init script with the measurement id', function (): void {
    $ga = new GoogleAnalytics4('G-ABCDEF1234');

    $html = $ga->initScript();

    expect($html)
        ->toContain('googletagmanager.com/gtag/js?id=G-ABCDEF1234')
        ->toContain("gtag('config', 'G-ABCDEF1234'")
        ->toContain('"anonymize_ip":true');
});

test('returns empty string when disabled', function (): void {
    expect((new GoogleAnalytics4('', true, false))->initScript())->toBe('');
    expect((new GoogleAnalytics4('G-X', true, false))->initScript())->toBe('');
});

test('track helpers build gtag calls', function (): void {
    $ga = new GoogleAnalytics4('G-ABC123');

    expect($ga->formSubmission('contact'))
        ->toContain("gtag('event', \"form_submission\"")
        ->toContain('"form_name":"contact"');

    expect($ga->ctaClick('Buy now', 'hero'))
        ->toContain('"label":"Buy now"')
        ->toContain('"location":"hero"');

    $purchase = $ga->purchase([['item_id' => 'sku1', 'price' => 19.99]], 'tx-1', 19.99, 'USD');
    expect($purchase)
        ->toContain('"transaction_id":"tx-1"')
        ->toContain('"currency":"USD"')
        ->toContain('"item_id":"sku1"');
});
