<?php

declare(strict_types=1);

use Capell\Themes\Agency\Data\AgencyThemeSettings;
use Capell\Themes\Agency\SEO\StructuredDataGenerator;

beforeEach(function () {
    $this->settings = new AgencyThemeSettings(
        organization_name: 'Studio Example',
        organization_logo_url: 'https://example.com/logo.png',
        organization_description: 'A creative studio.',
        social_instagram: 'https://instagram.com/studio',
        social_dribbble: 'https://dribbble.com/studio',
        social_behance: 'https://behance.net/studio',
    );
    $this->generator = new StructuredDataGenerator($this->settings);
});

test('organization includes name, logo, description and sameAs with social channels', function () {
    $data = $this->generator->organization('https://example.com');

    expect($data['@type'])->toBe('Organization')
        ->and($data['name'])->toBe('Studio Example')
        ->and($data['logo'])->toBe('https://example.com/logo.png')
        ->and($data['description'])->toBe('A creative studio.')
        ->and($data['sameAs'])->toContain(
            'https://instagram.com/studio',
            'https://dribbble.com/studio',
            'https://behance.net/studio'
        );
});

test('website includes SearchAction', function () {
    $data = $this->generator->website('https://example.com');

    expect($data['@type'])->toBe('WebSite')
        ->and($data['potentialAction']['@type'])->toBe('SearchAction')
        ->and($data['potentialAction']['target'])->toContain('search?q=');
});

test('creativeWork filters null fields and wraps creator as Organization', function () {
    $data = $this->generator->creativeWork([
        'name' => 'Northwind rebrand',
        'creator' => 'Capell',
        'dateCreated' => '2026-01-15',
    ]);

    expect($data['@type'])->toBe('CreativeWork')
        ->and($data['name'])->toBe('Northwind rebrand')
        ->and($data['creator'])->toBe(['@type' => 'Organization', 'name' => 'Capell'])
        ->and($data)->not->toHaveKey('description');
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
        ['name' => 'Work', 'url' => '/work'],
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
