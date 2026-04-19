<?php

declare(strict_types=1);

use Capell\Themes\Core\Theme\ThemeRegistrar;

beforeEach(function (): void {
    ThemeRegistrar::reset();
});

test('register() and options() round-trip', function (): void {
    ThemeRegistrar::register('acme', 'Acme Theme');
    expect(ThemeRegistrar::options())->toBe(['acme' => 'Acme Theme']);
});

test('multiple registrations are all returned', function (): void {
    ThemeRegistrar::register('alpha', 'Alpha Theme');
    ThemeRegistrar::register('beta', 'Beta Theme');
    expect(ThemeRegistrar::options())->toHaveCount(2);
});

test('isRegistered() returns true after registration', function (): void {
    ThemeRegistrar::register('cool', 'Cool Theme');
    expect(ThemeRegistrar::isRegistered('cool'))->toBeTrue();
    expect(ThemeRegistrar::isRegistered('other'))->toBeFalse();
});

test('reset() clears all registrations', function (): void {
    ThemeRegistrar::register('temp', 'Temp Theme');
    ThemeRegistrar::reset();
    expect(ThemeRegistrar::options())->toBe([]);
});
