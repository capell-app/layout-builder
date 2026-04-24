<?php

declare(strict_types=1);

use Capell\Themes\Core\Language\LanguageManager;

test('returns enabled locale codes', function (): void {
    $manager = new LanguageManager([
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'short' => 'en'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'dir' => 'ltr', 'short' => 'fr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl', 'short' => 'ar'],
    ], 'en');

    expect($manager->enabled())->toBe(['en', 'fr', 'ar']);
    expect($manager->fallback())->toBe('en');
});

test('returns direction and rtl detection', function (): void {
    $manager = new LanguageManager([
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'short' => 'en'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl', 'short' => 'ar'],
    ], 'en');

    expect($manager->direction('ar'))->toBe('rtl');
    expect($manager->isRtl('ar'))->toBeTrue();
    expect($manager->isRtl('en'))->toBeFalse();
});

test('falls back to sensible short code when locale missing', function (): void {
    $manager = new LanguageManager([], 'en');

    expect($manager->shortCode('zh-CN'))->toBe('zh');
    expect($manager->direction('zz'))->toBe('ltr');
});
