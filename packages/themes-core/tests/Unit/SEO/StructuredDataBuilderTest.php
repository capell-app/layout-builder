<?php

declare(strict_types=1);

use Capell\SeoTools\Support\StructuredDataBuilder;

test('organization schema renders valid JSON-LD with @type Organization', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->organization('Acme Corp', 'https://example.com');

    $schemas = $builder->toArray();

    expect($schemas)->toHaveCount(1);
    expect($schemas[0]['@type'])->toBe('Organization');
    expect($schemas[0]['name'])->toBe('Acme Corp');
    expect($schemas[0]['url'])->toBe('https://example.com');
    expect($schemas[0]['@context'])->toBe('https://schema.org');
});

test('BreadcrumbList renders correctly with ItemList structure', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->breadcrumbList([
        ['name' => 'Home', 'url' => 'https://example.com'],
        ['name' => 'About', 'url' => 'https://example.com/about'],
    ]);

    $schemas = $builder->toArray();

    expect($schemas[0]['@type'])->toBe('BreadcrumbList');
    expect($schemas[0]['itemListElement'])->toHaveCount(2);
    expect($schemas[0]['itemListElement'][0]['@type'])->toBe('ListItem');
    expect($schemas[0]['itemListElement'][0]['position'])->toBe(1);
    expect($schemas[0]['itemListElement'][0]['name'])->toBe('Home');
    expect($schemas[0]['itemListElement'][1]['position'])->toBe(2);
});

test('FAQPage renders with Question and Answer pairs', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->faqPage([
        ['question' => 'What is Capell?', 'answer' => 'A CMS platform.'],
        ['question' => 'Is it free?', 'answer' => 'Yes.'],
    ]);

    $schemas = $builder->toArray();

    expect($schemas[0]['@type'])->toBe('FAQPage');
    expect($schemas[0]['mainEntity'])->toHaveCount(2);
    expect($schemas[0]['mainEntity'][0]['@type'])->toBe('Question');
    expect($schemas[0]['mainEntity'][0]['name'])->toBe('What is Capell?');
    expect($schemas[0]['mainEntity'][0]['acceptedAnswer']['@type'])->toBe('Answer');
    expect($schemas[0]['mainEntity'][0]['acceptedAnswer']['text'])->toBe('A CMS platform.');
});

test('article schema includes all expected fields', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->article(
        headline: 'Hello World',
        description: 'A blog post',
        url: 'https://example.com/blog/hello',
        datePublished: '2024-01-01',
        author: 'Jane Doe',
    );

    $schemas = $builder->toArray();

    expect($schemas[0]['@type'])->toBe('Article');
    expect($schemas[0]['headline'])->toBe('Hello World');
    expect($schemas[0]['description'])->toBe('A blog post');
    expect($schemas[0]['url'])->toBe('https://example.com/blog/hello');
    expect($schemas[0]['datePublished'])->toBe('2024-01-01');
    expect($schemas[0]['author']['name'])->toBe('Jane Doe');
});

test('render() wraps each schema in its own script tag', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->organization('Acme', 'https://example.com');
    $builder->webPage('Home', 'Welcome', 'https://example.com');

    $rendered = $builder->render();

    expect(substr_count($rendered, '<script type="application/ld+json">'))->toBe(2);
    expect($rendered)->toContain('Organization');
    expect($rendered)->toContain('WebPage');
});

test('address() throws LogicException when called on a fresh builder', function (): void {
    $builder = new StructuredDataBuilder;

    expect(fn (): StructuredDataBuilder => $builder->address('123 Main St', 'Springfield', 'US'))
        ->toThrow(LogicException::class, 'address() requires an existing schema');
});

test('address() attaches address schema to the last schema', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->organization('Acme Corp', 'https://example.com')
        ->address('123 Main St', 'Springfield', 'US', '12345');

    $schemas = $builder->toArray();

    expect($schemas[0])->toHaveKey('address');
    expect($schemas[0]['address']['@type'])->toBe('PostalAddress');
    expect($schemas[0]['address']['streetAddress'])->toBe('123 Main St');
    expect($schemas[0]['address']['addressLocality'])->toBe('Springfield');
    expect($schemas[0]['address']['addressCountry'])->toBe('US');
    expect($schemas[0]['address']['postalCode'])->toBe('12345');
});

test('contactPoint() throws LogicException when called on a fresh builder', function (): void {
    $builder = new StructuredDataBuilder;

    expect(fn (): StructuredDataBuilder => $builder->contactPoint('hello@example.com'))
        ->toThrow(LogicException::class, 'contactPoint() requires an existing schema');
});

test('contactPoint() attaches contact schema to the last schema', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->organization('Acme Corp', 'https://example.com')
        ->contactPoint('hello@example.com', '+1-555-0100', 'sales');

    $schemas = $builder->toArray();

    expect($schemas[0])->toHaveKey('contactPoint');
    expect($schemas[0]['contactPoint']['@type'])->toBe('ContactPoint');
    expect($schemas[0]['contactPoint']['email'])->toBe('hello@example.com');
    expect($schemas[0]['contactPoint']['telephone'])->toBe('+1-555-0100');
    expect($schemas[0]['contactPoint']['contactType'])->toBe('sales');
});

test('webPage() creates a WebPage schema with the correct fields', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->webPage('About Us', 'Learn more about Acme Corp', 'https://example.com/about');

    $schemas = $builder->toArray();

    expect($schemas)->toHaveCount(1);
    expect($schemas[0]['@type'])->toBe('WebPage');
    expect($schemas[0]['@context'])->toBe('https://schema.org');
    expect($schemas[0]['name'])->toBe('About Us');
    expect($schemas[0]['description'])->toBe('Learn more about Acme Corp');
    expect($schemas[0]['url'])->toBe('https://example.com/about');
});

test('toArray() returns the raw schemas array', function (): void {
    $builder = new StructuredDataBuilder;

    expect($builder->toArray())->toBe([]);

    $builder->webPage('About', 'About us', 'https://example.com/about');

    $schemas = $builder->toArray();
    expect($schemas)->toHaveCount(1);
    expect($schemas[0])->toBeArray();
});
