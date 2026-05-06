<?php

declare(strict_types=1);

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;

/**
 * These tests boot the same set of packages PackagesTestCase already boots
 * (Address + LayoutBuilder + Blog + SeoSuite + Frontend + Admin) and assert the
 * registries are healthy. They guard the seam between packages — a problem
 * here is one no per-package suite can see.
 */
it('boots all packages without throwing', function (): void {
    // setUp() in PackagesTestCase wires in every package provider in the
    // fixture's set. Reaching this assertion at all means the boot sequence
    // didn't throw. The loaded-providers list is the smoke we check on top.
    expect($this->app->getLoadedProviders())->not->toBeEmpty();

    $providers = array_keys($this->app->getLoadedProviders());

    expect($providers)->toContain(AddressServiceProvider::class);
    expect($providers)->toContain(LayoutBuilderServiceProvider::class);
    expect($providers)->toContain(BlogServiceProvider::class);
    expect($providers)->toContain(BlockLibraryServiceProvider::class);
    expect($providers)->toContain(SeoSuiteServiceProvider::class);
});

it('every registered page type is a usable PageTypeData', function (): void {
    $types = CapellCore::getPageTypes();

    expect($types)->not->toBeEmpty();

    foreach ($types as $name => $type) {
        expect($name)->toBeString()->not->toBe('');
        expect($type->name)->toBe($name);
    }
});

it('every registered admin configurator points to a real class implementing ConfiguratorInterface', function (): void {
    $configurators = AdminSurfaceLookup::configuratorGroups();

    expect($configurators)->toBeArray();

    foreach ($configurators as $type => $perType) {
        foreach ($perType as $key => $class) {
            expect(class_exists($class))->toBeTrue(sprintf('Configurator class %s for type %s/%s does not exist', $class, $type, $key));
            expect(is_a($class, ConfiguratorInterface::class, true))
                ->toBeTrue(sprintf('Configurator class %s does not implement ConfiguratorInterface', $class));
        }
    }
});

it('every ConfiguratorTypeEnum value resolves to a configurator map', function (): void {
    foreach (ConfiguratorTypeEnum::cases() as $case) {
        $registered = AdminSurfaceLookup::configurators($case);

        expect($registered)->toBeArray();

        // Empty is fine — not every type has schemas in this fixture's package set.
        // What we are guarding: registration didn't throw and the array shape is
        // map<string, class-string>.
        foreach ($registered as $key => $class) {
            expect($key)->toBeString();
            expect(is_string($class) && class_exists($class))->toBeTrue(
                sprintf('Configurator %s registered under %s/%s is not a loadable class', $class, $case->value, $key),
            );
        }
    }
});

it('ConfiguratorTypeEnumInterface contract is satisfied by ConfiguratorTypeEnum', function (): void {
    expect(is_a(ConfiguratorTypeEnum::class, ConfiguratorTypeEnumInterface::class, true))->toBeTrue();
});
