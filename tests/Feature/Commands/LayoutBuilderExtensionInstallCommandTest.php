<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\CapellExtension;
use Capell\Core\Support\Manifest\CapellManifestData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    CapellCore::clearPackages();
});

afterEach(function (): void {
    CapellCore::clearPackages();
});

it('forces migrations when installing layout builder directly', function (): void {
    $migrateForceOptions = [];

    Artisan::command('capell:publish-migrations {--items=*}', fn (): int => Command::SUCCESS);

    Artisan::command('migrate {--force}', function () use (&$migrateForceOptions): int {
        $migrateForceOptions[] = $this->option('force');

        return Command::SUCCESS;
    });

    test()->artisan('capell:layout-builder-install')
        ->assertSuccessful();

    expect($migrateForceOptions)->toBe([true]);
});

it('installs layout builder from its package manifest', function (): void {
    $installCalls = [];

    Artisan::command('capell:layout-builder-install', function () use (&$installCalls): int {
        $installCalls[] = 'layout-builder';

        return Command::SUCCESS;
    });

    $manifestPath = dirname(__DIR__, 3) . '/capell.json';
    $manifestData = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    throw_unless(is_array($manifestData), RuntimeException::class, 'Layout builder manifest must decode to an array.');

    $requiredPackages = $manifestData['dependencies']['requires'] ?? [];
    throw_unless(is_array($requiredPackages), RuntimeException::class, 'Layout builder manifest must define required packages.');

    expect($requiredPackages)
        ->toContain('capell-app/core')
        ->toContain('capell-app/frontend');

    CapellCore::registerManifestPackage(CapellManifestData::fromArray(
        $manifestData,
        dirname($manifestPath),
    ));

    foreach ($requiredPackages as $requiredPackage) {
        if (is_string($requiredPackage)) {
            CapellCore::registerPackage($requiredPackage);
            CapellCore::forcePackageInstalled($requiredPackage);
        }
    }

    test()->artisan('capell:extension-install', [
        'extension' => 'capell-app/layout-builder',
    ])
        ->expectsOutput('Installing extension: capell-app/layout-builder')
        ->assertSuccessful();

    $extension = CapellExtension::query()
        ->where('composer_name', 'capell-app/layout-builder')
        ->first();

    expect($installCalls)->toBe(['layout-builder'])
        ->and($extension)->not->toBeNull()
        ->and($extension->status->value)->toBe('enabled')
        ->and($extension->installed_at)->not->toBeNull()
        ->and(CapellCore::isPackageInstalled('capell-app/layout-builder'))->toBeTrue();
});
