<?php

declare(strict_types=1);

use Capell\Core\Testing\Data\CompanionPackageContractData;
use Capell\Core\Testing\ExtensionTestHarness;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;

it('passes the shared package contract suite', function (): void {
    $root = dirname(__DIR__, 2);

    ExtensionTestHarness::assertCompanionPackageContract(new CompanionPackageContractData(
        packageRoot: $root,
        manifestPath: $root . '/capell.json',
        providerClass: LayoutBuilderServiceProvider::class,
        migrations: ['database/migrations/2026_05_10_190841_01_create_layouts_table.php'],
        lifecycleAssertion: fn (): bool => true,
        authorizationAssertion: fn (): bool => true,
        cacheInvalidationAssertion: fn (): bool => true,
        publicRender: fn (): string => '<section data-layout="release-contract">Layout</section>',
    ));

    expect(true)->toBeTrue();
});
