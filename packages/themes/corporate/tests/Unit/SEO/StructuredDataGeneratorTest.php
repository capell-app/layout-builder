<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Data\CorporateThemeSettings;
use Capell\Themes\Corporate\SEO\StructuredDataGenerator;

beforeEach(function () {
    $this->settings = new CorporateThemeSettings(
        organization_name: 'Acme Corp',
        organization_logo_url: 'https://example.com/logo.png',
        organization_description: 'We make widgets.',
        social_twitter: 'https://twitter.com/acme',
        social_linkedin: 'https://linkedin.com/company/acme',
    );
    $this->generator = new StructuredDataGenerator($this->settings);
});

test('organization includes name, logo, description and sameAs', function () {
    $data = $this->generator->organization('https://example.com');

    expect($data['@type'])->toBe('Organization')
        ->and($data['name'])->toBe('Acme Corp')
        ->and($data['logo'])->toBe('https://example.com/logo.png')
        ->and($data['description'])->toBe('We make widgets.')
        ->and($data['sameAs'])->toContain('https://twitter.com/acme', 'https://linkedin.com/company/acme');
});

test('website includes SearchAction', function () {
    $data = $this->generator->website('https://example.com');

    expect($data['@type'])->toBe('WebSite')
        ->and($data['potentialAction']['@type'])->toBe('SearchAction')
        ->and($data['potentialAction']['target'])->toContain('search?q=');
});

test('article filters null fields', function () {
    $data = $this->generator->article([
        'headline' => 'Hello',
        'datePublished' => '2026-04-19',
    ]);

    expect($data['headline'])->toBe('Hello')
        ->and($data)->not->toHaveKey('description')
        ->and($data['datePublished'])->toBe('2026-04-19')
        ->and($data['dateModified'])->toBe('2026-04-19');
});

test('breadcrumb indexes items from 1', function () {
    $data = $this->generator->breadcrumb([
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Blog', 'url' => '/blog'],
    ]);

    expect($data['@type'])->toBe('BreadcrumbList')
        ->and($data['itemListElement'][0]['position'])->toBe(1)
        ->and($data['itemListElement'][1]['position'])->toBe(2);
});

test('faq wraps questions and answers', function () {
    $data = $this->generator->faq([
        ['question' => 'Q1?', 'answer' => 'A1.'],
    ]);

    expect($data['@type'])->toBe('FAQPage')
        ->and($data['mainEntity'][0]['name'])->toBe('Q1?')
        ->and($data['mainEntity'][0]['acceptedAnswer']['text'])->toBe('A1.');
});

test('toJsonLd produces valid JSON', function () {
    $json = $this->generator->toJsonLd(['@type' => 'Test', 'name' => 'hello']);

    expect($json)->toBeString();
    $decoded = json_decode($json, true);
    expect($decoded)->toBe(['@type' => 'Test', 'name' => 'hello']);
});
