<?php

declare(strict_types=1);

use Capell\SeoSuite\Support\StructuredDataBuilder;

it('builds organization schema with address and contact point data', function (): void {
    $schemas = (new StructuredDataBuilder)
        ->organization('Capell', 'https://example.com', 'https://example.com/logo.png')
        ->address('1 Test Street', 'Bristol', 'GB', 'BS1 1AA')
        ->contactPoint('support@example.com', '+441234567890', 'support')
        ->toArray();

    expect($schemas)->toHaveCount(1)
        ->and($schemas[0]['@type'])->toBe('Organization')
        ->and($schemas[0]['logo'])->toBe('https://example.com/logo.png')
        ->and($schemas[0]['address'])->toMatchArray([
            '@type' => 'PostalAddress',
            'streetAddress' => '1 Test Street',
            'addressLocality' => 'Bristol',
            'addressCountry' => 'GB',
            'postalCode' => 'BS1 1AA',
        ])
        ->and($schemas[0]['contactPoint'])->toMatchArray([
            '@type' => 'ContactPoint',
            'email' => 'support@example.com',
            'telephone' => '+441234567890',
            'contactType' => 'support',
        ]);
});

it('builds page, article, breadcrumb, and faq schemas as json ld script tags', function (): void {
    $output = (new StructuredDataBuilder)
        ->webPage('About', 'About Capell', 'https://example.com/about')
        ->article('Launch', 'Launch notes', 'https://example.com/blog/launch', '2026-05-07', 'Ben Johnson')
        ->breadcrumbList([
            ['name' => 'Home', 'url' => 'https://example.com'],
            ['name' => 'About', 'url' => 'https://example.com/about'],
        ])
        ->faqPage([
            ['question' => 'What is Capell?', 'answer' => 'A CMS platform.'],
        ])
        ->render();

    expect($output)->toContain('<script type="application/ld+json">')
        ->and($output)->toContain('"@type":"WebPage"')
        ->and($output)->toContain('"@type":"Article"')
        ->and($output)->toContain('"author":{"@type":"Person","name":"Ben Johnson"}')
        ->and($output)->toContain('"@type":"BreadcrumbList"')
        ->and($output)->toContain('"position":2')
        ->and($output)->toContain('"@type":"FAQPage"')
        ->and($output)->toContain('"acceptedAnswer":{"@type":"Answer","text":"A CMS platform."}');
});

it('escapes unsafe characters when rendering json ld', function (): void {
    $output = (new StructuredDataBuilder)
        ->webPage('<About>', 'Tom & "Jerry"', 'https://example.com/about?name=Tom&quote="yes"')
        ->render();

    expect($output)->toContain('\u003CAbout\u003E')
        ->and($output)->toContain('Tom \u0026 \u0022Jerry\u0022')
        ->and($output)->toContain('https://example.com/about?name=Tom\u0026quote=\u0022yes\u0022');
});

it('requires a parent schema before adding address or contact point data', function (): void {
    expect(fn (): StructuredDataBuilder => (new StructuredDataBuilder)->address('Street', 'City', 'GB'))
        ->toThrow(LogicException::class, 'address() requires an existing schema');

    expect(fn (): StructuredDataBuilder => (new StructuredDataBuilder)->contactPoint('support@example.com'))
        ->toThrow(LogicException::class, 'contactPoint() requires an existing schema');
});
