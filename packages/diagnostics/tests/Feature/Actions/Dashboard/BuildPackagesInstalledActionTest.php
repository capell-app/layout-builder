<?php

declare(strict_types=1);

use Capell\Diagnostics\Actions\Dashboard\BuildPackagesInstalledAction;
use Illuminate\Support\Facades\File;

it('hydrates installed package health metadata from local package manifests', function (): void {
    $temporaryRoot = sys_get_temp_dir() . '/capell_packages_installed_' . uniqid();
    $installedJsonPath = $temporaryRoot . '/vendor/composer/installed.json';
    $packagesPath = $temporaryRoot . '/packages';
    $packagePath = $packagesPath . '/events';

    File::ensureDirectoryExists(dirname($installedJsonPath));
    File::ensureDirectoryExists($packagePath . '/config');

    File::put($installedJsonPath, json_encode([
        'packages' => [
            ['name' => 'capell-app/events', 'version' => '4.x-dev'],
            ['name' => 'vendor/ignored', 'version' => '1.0.0'],
        ],
    ], JSON_THROW_ON_ERROR));

    File::put($packagePath . '/config/capell-events.php', '<?php return [];');
    File::put($packagePath . '/capell.json', json_encode([
        'name' => 'capell-app/events',
        'slug' => 'events',
        'displayName' => 'Events',
        'product' => ['bundle' => 'growth'],
        'commands' => [
            'install' => 'capell:events-install',
            'doctor' => 'capell:events-doctor',
        ],
        'healthChecks' => [
            ['name' => 'tables'],
            ['name' => 'routes'],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $result = (new BuildPackagesInstalledAction($installedJsonPath, $packagesPath))->handle();
        $package = $result->packages->toCollection()->first();

        expect($result->packages)->toHaveCount(1)
            ->and($package->name)->toBe('events')
            ->and($package->displayName)->toBe('Events')
            ->and($package->bundle)->toBe('growth')
            ->and($package->healthCheckCount)->toBe(2)
            ->and($package->installCommand)->toBe('capell:events-install')
            ->and($package->doctorCommand)->toBe('capell:events-doctor');
    } finally {
        File::deleteDirectory($temporaryRoot);
    }
});

it('still lists unknown capell packages when no local manifest is available', function (): void {
    $temporaryRoot = sys_get_temp_dir() . '/capell_packages_installed_' . uniqid();
    $installedJsonPath = $temporaryRoot . '/vendor/composer/installed.json';
    $packagesPath = $temporaryRoot . '/packages';

    File::ensureDirectoryExists(dirname($installedJsonPath));
    File::ensureDirectoryExists($packagesPath);
    File::put($installedJsonPath, json_encode([
        'packages' => [
            ['name' => 'capell-app/custom-package', 'version' => 'dev-main'],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $result = (new BuildPackagesInstalledAction($installedJsonPath, $packagesPath))->handle();
        $package = $result->packages->toCollection()->first();

        expect($result->packages)->toHaveCount(1)
            ->and($package->name)->toBe('custom-package')
            ->and($package->docsUrl)->toBeNull()
            ->and($package->healthCheckCount)->toBe(0);
    } finally {
        File::deleteDirectory($temporaryRoot);
    }
});

it('hydrates installed package metadata from composer install paths', function (): void {
    $temporaryRoot = sys_get_temp_dir() . '/capell_packages_installed_' . uniqid();
    $installedJsonPath = $temporaryRoot . '/vendor/composer/installed.json';
    $packagePath = $temporaryRoot . '/vendor/capell-app/events';
    $packagesPath = $temporaryRoot . '/missing-packages';

    File::ensureDirectoryExists(dirname($installedJsonPath));
    File::ensureDirectoryExists($packagePath . '/config');

    File::put($installedJsonPath, json_encode([
        'packages' => [
            [
                'name' => 'capell-app/events',
                'version' => '4.x-dev',
                'install_path' => '../capell-app/events',
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    File::put($packagePath . '/config/capell-events.php', '<?php return [];');
    File::put($packagePath . '/capell.json', json_encode([
        'name' => 'capell-app/events',
        'slug' => 'events',
        'displayName' => 'Events',
        'product' => ['bundle' => 'growth'],
        'commands' => [
            'install' => 'capell:events-install',
            'doctor' => 'capell:events-doctor',
        ],
        'healthChecks' => [
            ['name' => 'tables'],
            ['name' => 'routes'],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $result = (new BuildPackagesInstalledAction($installedJsonPath, $packagesPath))->handle();
        $package = $result->packages->toCollection()->first();

        expect($result->packages)->toHaveCount(1)
            ->and($package->name)->toBe('events')
            ->and($package->displayName)->toBe('Events')
            ->and($package->bundle)->toBe('growth')
            ->and($package->healthCheckCount)->toBe(2)
            ->and($package->installCommand)->toBe('capell:events-install')
            ->and($package->doctorCommand)->toBe('capell:events-doctor');
    } finally {
        File::deleteDirectory($temporaryRoot);
    }
});

it('reads health check counts from real package manifests', function (): void {
    $temporaryRoot = sys_get_temp_dir() . '/capell_packages_installed_' . uniqid();
    $installedJsonPath = $temporaryRoot . '/vendor/composer/installed.json';

    File::ensureDirectoryExists(dirname($installedJsonPath));
    File::put($installedJsonPath, json_encode([
        'packages' => [
            ['name' => 'capell-app/api', 'version' => '4.x-dev'],
            ['name' => 'capell-app/diagnostics', 'version' => '4.x-dev'],
            ['name' => 'capell-app/migration-assistant', 'version' => '4.x-dev'],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $packagesPath = dirname(__DIR__, 5);
        $result = (new BuildPackagesInstalledAction($installedJsonPath, $packagesPath))->handle();
        $packages = $result->packages->toCollection()->keyBy('composerName');

        expect($packages)->toHaveKeys([
            'capell-app/api',
            'capell-app/diagnostics',
            'capell-app/migration-assistant',
        ])
            ->and($packages->get('capell-app/api')->healthCheckCount)->toBeGreaterThan(0)
            ->and($packages->get('capell-app/diagnostics')->healthCheckCount)->toBeGreaterThan(0)
            ->and($packages->get('capell-app/migration-assistant')->healthCheckCount)->toBeGreaterThan(0);
    } finally {
        File::deleteDirectory($temporaryRoot);
    }
});
