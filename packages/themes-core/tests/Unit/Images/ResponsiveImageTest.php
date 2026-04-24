<?php

declare(strict_types=1);

use Capell\Themes\Core\Images\ResponsiveImage;

test('builds srcset with default widths', function (): void {
    $image = new ResponsiveImage;

    $srcset = $image->buildSrcset('https://example.com/a.jpg');

    expect($srcset)
        ->toContain('https://example.com/a.jpg?w=400 400w')
        ->toContain('https://example.com/a.jpg?w=800 800w')
        ->toContain('https://example.com/a.jpg?w=1200 1200w')
        ->toContain('https://example.com/a.jpg?w=1600 1600w');
});

test('renders img tag with lazy loading and aspect-ratio', function (): void {
    $image = new ResponsiveImage;

    $html = $image->render('https://example.com/a.jpg', 'A field at dusk', 1600, 900);

    expect($html)
        ->toContain('<img ')
        ->toContain('alt="A field at dusk"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"')
        ->toContain('width="1600"')
        ->toContain('height="900"')
        ->toContain('aspect-ratio: 1600 / 900');
});

test('supports custom url transformer', function (): void {
    $image = new ResponsiveImage(
        urlTransformer: static fn (string $src, int $w): string => $src . '-' . $w . '.webp',
        widths: [300, 600],
    );

    $srcset = $image->buildSrcset('https://cdn.example.com/photo');

    expect($srcset)->toBe('https://cdn.example.com/photo-300.webp 300w, https://cdn.example.com/photo-600.webp 600w');
});

test('renders picture with webp sources', function (): void {
    $image = new ResponsiveImage;

    $html = $image->renderPicture(
        'https://example.com/a.jpg',
        'caption',
        1200,
        800,
        ['(min-width: 1024px)' => 'https://example.com/a.webp'],
    );

    expect($html)
        ->toContain('<picture>')
        ->toContain('type="image/webp"')
        ->toContain('media="(min-width: 1024px)"');
});
