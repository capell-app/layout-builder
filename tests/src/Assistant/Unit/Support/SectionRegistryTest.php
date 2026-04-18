<?php

declare(strict_types=1);

use Capell\Assistant\Support\SectionRegistry;

it('registers and retrieves section descriptors', function (): void {
    $registry = new SectionRegistry;

    $registry->register('hero-fullwidth', [
        'label' => 'Full-width Hero',
        'description' => 'Large banner',
        'good_for' => ['landing pages'],
        'not_for' => ['blog posts'],
        'fields' => ['headline', 'subheading'],
        'media' => ['background_image'],
        'supports_translations' => true,
        'repeatable' => false,
    ]);

    expect($registry->all())->toHaveKey('hero-fullwidth')
        ->and($registry->all()['hero-fullwidth']['label'])->toBe('Full-width Hero');
});

it('formats sections for AI prompt context', function (): void {
    $registry = new SectionRegistry;

    $registry->register('text-block', [
        'label' => 'Text Block',
        'description' => 'Rich text paragraph block',
        'good_for' => ['articles', 'about pages'],
        'not_for' => [],
        'fields' => ['body'],
        'media' => [],
        'supports_translations' => true,
        'repeatable' => true,
    ]);

    $output = $registry->forAi();

    expect($output)->toContain('text-block')
        ->and($output)->toContain('Text Block')
        ->and($output)->toContain('articles');
});

it('returns empty collection when no sections registered', function (): void {
    $registry = new SectionRegistry;

    expect($registry->all())->toBeEmpty()
        ->and($registry->forAi())->toBe('No section types registered.');
});
