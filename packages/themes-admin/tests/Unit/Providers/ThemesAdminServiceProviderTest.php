<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Capell\Themes\Admin\Settings\ThemeAdminSettings;
use Capell\Themes\Admin\ThemesAdminServiceProvider;

test('provider registers themes package metadata', function (): void {
    $package = CapellCore::getPackage(ThemesAdminServiceProvider::$packageName);

    expect($package->serviceProviderClass)->toBe(ThemesAdminServiceProvider::class)
        ->and($package->setting)->toBe(ThemeAdminSettings::class);
});

test('provider registers theme settings through the central settings registry', function (): void {
    /** @var SettingsSchemaRegistry $registry */
    $registry = resolve(SettingsSchemaRegistry::class);

    expect($registry->getSettingsClass(ThemeAdminSettings::group()))->toBe(ThemeAdminSettings::class)
        ->and($registry->getSchema(ThemeAdminSettings::group(), 'ThemeSettingsSchema'))->toBe(ThemeSettingsSchema::class);
});

test('package uses the guarded central settings page instead of an unguarded standalone page', function (): void {
    expect(class_exists('Capell\\Themes\\Admin\\Pages\\ThemeSettingsPage'))->toBeFalse()
        ->and(CapellAdmin::getExtraPages())->not->toContain('Capell\\Themes\\Admin\\Pages\\ThemeSettingsPage');
});
