<?php

declare(strict_types=1);

use Capell\Themes\Saas\Data\SaasThemeSettings;
use Capell\Themes\Saas\SEO\StructuredDataGenerator;

test('structured data output embeds as valid json-ld', function () {
    $generator = new StructuredDataGenerator(new SaasThemeSettings);
    $html = $generator->toJsonLd($generator->organization('https://example.com'));

    expect(json_decode($html, true))->toBeArray()->toHaveKey('@context');
});

test('structured data supports software app + pricing product + faq + breadcrumb in one page', function () {
    $generator = new StructuredDataGenerator(new SaasThemeSettings);

    $app = $generator->softwareApplication(['name' => 'Capell']);
    $product = $generator->product('Capell', [
        ['name' => 'Growth', 'price_monthly' => 49, 'currency' => 'USD'],
    ]);
    $breadcrumb = $generator->breadcrumb([['name' => 'Home', 'url' => '/']]);
    $faq = $generator->faq([['question' => 'Q?', 'answer' => 'A.']]);

    expect($app['@type'])->toBe('SoftwareApplication')
        ->and($product['@type'])->toBe('Product')
        ->and($breadcrumb['@type'])->toBe('BreadcrumbList')
        ->and($faq['@type'])->toBe('FAQPage');
});
