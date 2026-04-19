<?php

declare(strict_types=1);

use Capell\Themes\Agency\Data\AgencyThemeSettings;
use Capell\Themes\Agency\SEO\StructuredDataGenerator;

test('structured data output embeds as valid json-ld', function () {
    $generator = new StructuredDataGenerator(new AgencyThemeSettings);
    $html = $generator->toJsonLd($generator->organization('https://example.com'));

    expect(json_decode($html, true))->toBeArray()->toHaveKey('@context');
});

test('structured data supports creativeWork + breadcrumb + faq in one page', function () {
    $generator = new StructuredDataGenerator(new AgencyThemeSettings);

    $work = $generator->creativeWork(['name' => 'Northwind rebrand', 'creator' => 'Capell']);
    $breadcrumb = $generator->breadcrumb([['name' => 'Home', 'url' => '/']]);
    $faq = $generator->faq([['question' => 'Q?', 'answer' => 'A.']]);

    expect($work['@type'])->toBe('CreativeWork')
        ->and($breadcrumb['@type'])->toBe('BreadcrumbList')
        ->and($faq['@type'])->toBe('FAQPage');
});
