<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Data\CorporateThemeSettings;
use Capell\Themes\Corporate\SEO\StructuredDataGenerator;

test('structured data output embeds as valid json-ld', function () {
    $generator = new StructuredDataGenerator(new CorporateThemeSettings);
    $html = $generator->toJsonLd($generator->organization('https://example.com'));

    expect(json_decode($html, true))->toBeArray()->toHaveKey('@context');
});

test('structured data supports article + breadcrumb + faq in one page', function () {
    $generator = new StructuredDataGenerator(new CorporateThemeSettings);

    $article = $generator->article(['headline' => 'Hello']);
    $breadcrumb = $generator->breadcrumb([['name' => 'Home', 'url' => '/']]);
    $faq = $generator->faq([['question' => 'Q?', 'answer' => 'A.']]);

    expect($article['@type'])->toBe('Article')
        ->and($breadcrumb['@type'])->toBe('BreadcrumbList')
        ->and($faq['@type'])->toBe('FAQPage');
});
