<?php

declare(strict_types=1);

use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Models\Theme;
use Capell\DefaultTheme\Support\Interceptors\Themes\DefaultThemeInterceptor;

it('adds default meta values when meta key is missing', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = ['name' => 'Test Theme'];

    $result = $interceptor->beforeCreate($data);

    expect($result)->toHaveKey('meta');
    expect($result['meta'])->toHaveKey('header_border_color');
    expect($result['meta'])->toHaveKey('sticky_header');
    expect($result['meta'])->toHaveKey('dark_mode_toggle');
    expect($result['meta'])->toHaveKey('content_divider');
});

it('sets header_border_color to light gray', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = [];

    $result = $interceptor->beforeCreate($data);

    expect($result['meta']['header_border_color'])->toBe(DefaultColorEnum::LightGray->getColor());
});

it('sets sticky_header to true', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = [];

    $result = $interceptor->beforeCreate($data);

    expect($result['meta']['sticky_header'])->toBeTrue();
});

it('sets dark_mode_toggle to true', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = [];

    $result = $interceptor->beforeCreate($data);

    expect($result['meta']['dark_mode_toggle'])->toBeTrue();
});

it('sets content_divider to below_heading', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = [];

    $result = $interceptor->beforeCreate($data);

    expect($result['meta']['content_divider'])->toBe('below_heading');
});

it('merges with existing meta values', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = [
        'meta' => [
            'custom_key' => 'custom_value',
            'sticky_header' => false,
        ],
    ];

    $result = $interceptor->beforeCreate($data);

    expect($result['meta']['custom_key'])->toBe('custom_value');
    expect($result['meta']['sticky_header'])->toBeFalse();
    expect($result['meta'])->toHaveKey('header_border_color');
});

it('initializes meta array if not present', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = ['name' => 'Test'];

    $result = $interceptor->beforeCreate($data);

    expect($result['meta'])->toBeArray();
    expect(count($result['meta']))->toBeGreaterThan(0);
});

it('preserves other data fields', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $data = ['name' => 'Test Theme', 'description' => 'A test'];

    $result = $interceptor->beforeCreate($data);

    expect($result['name'])->toBe('Test Theme');
    expect($result['description'])->toBe('A test');
});

it('afterCreated method does nothing', function (): void {
    $interceptor = new DefaultThemeInterceptor;
    $theme = Theme::factory()->make();

    $interceptor->afterCreated($theme, []);

    expect(true)->toBeTrue();
});
