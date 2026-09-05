<?php

declare(strict_types=1);

use Capell\Core\Testing\Data\CompanionPackageContractData;
use Capell\Core\Testing\ExtensionTestHarness;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;

it('passes the shared package manifest contract suite', function (): void {
    $root = dirname(__DIR__, 2);

    ExtensionTestHarness::assertCompanionPackageContract(new CompanionPackageContractData(
        packageRoot: $root,
        manifestPath: $root . '/capell.json',
        providerClass: LayoutBuilderServiceProvider::class,
        migrations: ['database/migrations/2026_05_10_190841_02_create_widgets_table.php'],
    ));

    $summary = ExtensionTestHarness::forPath($root)->summary();

    expect($summary['package'])->toBe('capell-app/layout-builder')
        ->and($summary['migrations'])->toBeTrue()
        ->and($summary['providers'])->toBeGreaterThan(0);
});
