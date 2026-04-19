<?php

declare(strict_types=1);

use Capell\Themes\Core\Language\HreflangGenerator;
use Capell\Themes\Core\Language\LanguageManager;

test('generates entries for enabled locales plus x-default', function (): void {
    $manager = new LanguageManager([
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'short' => 'en'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'dir' => 'ltr', 'short' => 'fr'],
    ], 'en');

    $generator = new HreflangGenerator($manager);
    $entries = $generator->entries('https://example.com/en/about');

    $codes = array_column($entries, 'hreflang');
    expect($codes)->toContain('en', 'fr', 'x-default');

    $frEntry = collect($entries)->firstWhere('hreflang', 'fr');
    expect($frEntry['href'])->toBe('https://example.com/fr/about');
});

test('renders <link> tags escaping attributes', function (): void {
    $manager = new LanguageManager([
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'short' => 'en'],
    ], 'en');

    $html = (new HreflangGenerator($manager))->render('https://example.com/en');

    expect($html)
        ->toContain('rel="alternate"')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="x-default"');
});
