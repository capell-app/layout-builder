<?php

declare(strict_types=1);

use Capell\Core\Contracts\PackageLifecycleAction;
use Capell\Core\Contracts\ProgressReporter;
use Capell\Core\Data\PackageData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\CapellExtension;
use Capell\Core\Support\Manifest\CapellManifestData;
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderPackageAction;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderInstallRecorder;
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

    CapellCore::registerPackage('capell-app/layout-builder');

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
    Artisan::command('capell:publish-migrations {--items=*}', fn (): int => Command::SUCCESS);
    Artisan::command('migrate {--force} {--path=*} {--realpath}', fn (): int => Command::SUCCESS);
    $installRecorder = new LayoutBuilderInstallRecorder;

    test()->instance(InstallLayoutBuilderPackageAction::class, new readonly class($installRecorder) implements PackageLifecycleAction
    {
        public function __construct(private LayoutBuilderInstallRecorder $installRecorder) {}

        /**
         * @param  array<string, mixed>  $arguments
         */
        public function handle(PackageData $package, array $arguments = [], ?ProgressReporter $reporter = null): void
        {
            $this->installRecorder->calls[] = $package->name;
        }
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
    $extension = capell_test_instance($extension, CapellExtension::class);

    expect($installRecorder->calls)->toBe(['capell-app/layout-builder'])
        ->and($extension->status->value)->toBe('enabled')
        ->and($extension->installed_at)->not->toBeNull()
        ->and(CapellCore::isPackageInstalled('capell-app/layout-builder'))->toBeTrue();
});
