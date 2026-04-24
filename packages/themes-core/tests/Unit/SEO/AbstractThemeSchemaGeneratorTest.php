<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AbstractThemeSchemaGenerator;

$makeGenerator = (fn (): AbstractThemeSchemaGenerator => new class extends AbstractThemeSchemaGenerator
{
    protected function resolveOrgName(): string
    {
        return 'Stub Corp';
    }

    /** @return array<int, string> */
    protected function resolveSameAs(): array
    {
        return ['https://twitter.com/stub'];
    }
});

test('organization() includes name, url, and sameAs', function () use ($makeGenerator): void {
    $result = $makeGenerator()->organization('https://example.com');
    expect($result['@type'])->toBe('Organization');
    expect($result['name'])->toBe('Stub Corp');
    expect($result['url'])->toBe('https://example.com');
    expect($result['sameAs'])->toContain('https://twitter.com/stub');
});

test('website() includes potentialAction search', function () use ($makeGenerator): void {
    $result = $makeGenerator()->website('https://example.com', 'Stub');
    expect($result['@type'])->toBe('WebSite');
    expect($result['potentialAction']['@type'])->toBe('SearchAction');
});

test('breadcrumb() numbers items from 1', function () use ($makeGenerator): void {
    $result = $makeGenerator()->breadcrumb([
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Blog', 'url' => '/blog'],
    ]);
    expect($result['@type'])->toBe('BreadcrumbList');
    expect($result['itemListElement'][0]['position'])->toBe(1);
    expect($result['itemListElement'][1]['position'])->toBe(2);
});

test('faq() wraps items as Question/Answer pairs', function () use ($makeGenerator): void {
    $result = $makeGenerator()->faq([['question' => 'Q?', 'answer' => 'A.']]);
    expect($result['@type'])->toBe('FAQPage');
    expect($result['mainEntity'][0]['@type'])->toBe('Question');
});

test('toJsonLd() produces valid JSON with unescaped slashes', function () use ($makeGenerator): void {
    $json = $makeGenerator()->toJsonLd(['url' => 'https://example.com/page']);
    expect($json)->toContain('https://example.com/page');
    expect($json)->not->toContain('https:\/\/');
});

test('organization() includes logo when resolveOrgLogo returns a value', function (): void {
    $generator = new class extends AbstractThemeSchemaGenerator
    {
        protected function resolveOrgName(): string
        {
            return 'Logo Corp';
        }

        /** @return array<int, string> */
        protected function resolveSameAs(): array
        {
            return [];
        }

        protected function resolveOrgLogo(): string
        {
            return 'https://example.com/logo.png';
        }
    };

    $result = $generator->organization();
    expect($result)->toHaveKey('logo', 'https://example.com/logo.png');
});

test('organization() includes description when resolveOrgDescription returns a value', function (): void {
    $generator = new class extends AbstractThemeSchemaGenerator
    {
        protected function resolveOrgName(): string
        {
            return 'Desc Corp';
        }

        /** @return array<int, string> */
        protected function resolveSameAs(): array
        {
            return [];
        }

        protected function resolveOrgDescription(): string
        {
            return 'A fine company.';
        }
    };

    $result = $generator->organization();
    expect($result)->toHaveKey('description', 'A fine company.');
});

test('article() returns an Article node with required and optional fields', function () use ($makeGenerator): void {
    $result = $makeGenerator()->article([
        'headline' => 'My Article',
        'author' => 'Jane Doe',
        'datePublished' => '2026-01-01',
    ]);

    expect($result['@type'])->toBe('Article');
    expect($result['headline'])->toBe('My Article');
    expect($result['author']['name'])->toBe('Jane Doe');
    expect($result)->not->toHaveKey('image');
});
