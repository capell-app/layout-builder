<?php

declare(strict_types=1);

use Capell\Themes\Core\Performance\AssetOptimizer;

test('renders preload/preconnect/dns-prefetch hints', function (): void {
    $opt = new AssetOptimizer;
    $opt->preload('/hero.webp', 'image', 'image/webp')
        ->preconnect('https://fonts.gstatic.com')
        ->dnsPrefetch('//cdn.example.com');

    $html = $opt->render();

    expect($html)
        ->toContain('rel="preload"')
        ->toContain('as="image"')
        ->toContain('type="image/webp"')
        ->toContain('rel="preconnect"')
        ->toContain('crossorigin="anonymous"')
        ->toContain('rel="dns-prefetch"');
});
