<?php

declare(strict_types=1);

use Capell\Themes\Saas\Data\SaasThemeSettings;
use Capell\Themes\Saas\SEO\StructuredDataGenerator;

beforeEach(function () {
    $this->settings = new SaasThemeSettings(
        product_name: 'Acme SaaS',
        product_description: 'A powerful widget platform.',
        product_screenshot_url: 'https://example.com/screenshot.png',
        social_twitter: 'https://twitter.com/acme',
        social_linkedin: 'https://linkedin.com/company/acme',
        social_github: 'https://github.com/acme',
    );
    $this->generator = new StructuredDataGenerator($this->settings);
});

test('organization includes name, description and sameAs', function () {
    $data = $this->generator->organization('https://example.com');

    expect($data['@type'])->toBe('Organization')
        ->and($data['name'])->toBe('Acme SaaS')
        ->and($data['description'])->toBe('A powerful widget platform.')
        ->and($data['sameAs'])->toContain(
            'https://twitter.com/acme',
            'https://linkedin.com/company/acme',
            'https://github.com/acme',
        );
});

test('softwareApplication includes defaults and uses product screenshot', function () {
    $data = $this->generator->softwareApplication();

    expect($data['@type'])->toBe('SoftwareApplication')
        ->and($data['name'])->toBe('Acme SaaS')
        ->and($data['screenshot'])->toBe('https://example.com/screenshot.png')
        ->and($data['applicationCategory'])->toBe('BusinessApplication')
        ->and($data['operatingSystem'])->toBe('Web');
});

test('product embeds offer per pricing tier and skips tiers without a price', function () {
    $data = $this->generator->product('Acme', [
        ['name' => 'Starter', 'price_monthly' => 19, 'currency' => 'USD'],
        ['name' => 'Growth', 'price_monthly' => 49, 'currency' => 'USD'],
        ['name' => 'Enterprise'],
    ]);

    expect($data['@type'])->toBe('Product')
        ->and(count($data['offers']))->toBe(2)
        ->and($data['offers'][0]['@type'])->toBe('Offer')
        ->and($data['offers'][0]['priceCurrency'])->toBe('USD')
        ->and($data['offers'][0]['price'])->toBe('19');
});

test('breadcrumb indexes items from 1', function () {
    $data = $this->generator->breadcrumb([
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Pricing', 'url' => '/pricing'],
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

test('website includes SearchAction', function () {
    $data = $this->generator->website('https://example.com');

    expect($data['@type'])->toBe('WebSite')
        ->and($data['potentialAction']['@type'])->toBe('SearchAction')
        ->and($data['potentialAction']['target'])->toContain('search?q=');
});

test('toJsonLd produces valid JSON', function () {
    $json = $this->generator->toJsonLd(['@type' => 'Test', 'name' => 'hello']);

    expect($json)->toBeString();
    $decoded = json_decode($json, true);
    expect($decoded)->toBe(['@type' => 'Test', 'name' => 'hello']);
});
