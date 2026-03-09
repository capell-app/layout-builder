<?php

declare(strict_types=1);

namespace Capell\Tests\Support\Concerns;

use Capell\Core\Data\PackageData;
use Capell\Core\Facades\CapellCore;
use Illuminate\Support\Facades\File;
use RuntimeException;

trait BuildsOrderedMigrationWorkspace
{
    protected ?string $orderedMigrationWorkspacePath = null;

    private function discoverPackageMigrations(PackageData $package): array
    {
        $path = $package->path;

        if ($path === null) {
            return [];
        }

        $migrationPath = realpath($path . '/database/migrations');

        if ($migrationPath === false) {
            return [];
        }

        $orderedMigrations = $this->resolveOrderedPackageMigrations($package, $migrationPath);

        if ($orderedMigrations !== []) {
            return $orderedMigrations;
        }

        $files = glob($migrationPath . '/*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function resolveOrderedPackageMigrations(PackageData $package, string $migrationPath): array
    {
        $packageNamespace = str($package->name)
            ->after('/')
            ->replace('-', ' ')
            ->studly()
            ->replace(' ', '')
            ->toString();

        $managerClass = sprintf(
            'Capell\\%s\\Support\\Capell%sManager',
            $packageNamespace,
            $packageNamespace,
        );

        if (! class_exists($managerClass) || ! method_exists($managerClass, 'getMigrations')) {
            return [];
        }

        /** @var array<int, string> $migrationNames */
        $migrationNames = $managerClass::getMigrations();

        return array_values(array_filter(array_map(
            static fn (string $migrationName): string => sprintf('%s/%s.php', $migrationPath, $migrationName),
            $migrationNames,
        ), static fn (string $migrationFile): bool => File::exists($migrationFile)));
    }

    private function orderedMigrationWorkspacePath(): string
    {
        if (is_string($this->orderedMigrationWorkspacePath)) {
            return $this->orderedMigrationWorkspacePath;
        }

        $testToken = getenv('TEST_TOKEN');
        $cacheToken = is_string($testToken) && $testToken !== '' ? $testToken : 'default';
        $workspacePath = storage_path('framework/testing-migrations/' . md5(static::class . '|' . $cacheToken));

        File::ensureDirectoryExists($workspacePath);
        File::cleanDirectory($workspacePath);

        foreach ($this->orderedMigrationSourceFiles() as $index => $migrationPath) {
            $filename = $this->buildOrderedMigrationFilename($migrationPath, $index);

            File::copy($migrationPath, $workspacePath . DIRECTORY_SEPARATOR . $filename);
        }

        $this->orderedMigrationWorkspacePath = $workspacePath;

        return $workspacePath;
    }

    /**
     * @return array<int, string>
     */
    private function orderedMigrationSourceFiles(): array
    {
        $coreMigrationPath = realpath(__DIR__ . '/../../../../vendor/capell-app/core/database/migrations');

        if ($coreMigrationPath === false) {
            $coreMigrationPath = realpath(__DIR__ . '/../../../../vendor/capell-app/core/packages/core/database/migrations');
        }

        throw_unless($coreMigrationPath, RuntimeException::class, 'Could not find core migrations path.');

        $testMigrations = glob(__DIR__ . '/../../../database/migrations/*.php');

        if ($testMigrations === false) {
            $testMigrations = [];
        }

        sort($testMigrations);

        $coreMigrations = array_map(
            static fn (string $migration): string => sprintf('%s/%s.php', $coreMigrationPath, $migration),
            CapellCore::getMigrations(),
        );

        $packageMigrations = [];

        CapellCore::getInstalledPackages()->each(function (PackageData $package) use (&$packageMigrations): void {
            $packageMigrations = array_merge($packageMigrations, $this->discoverPackageMigrations($package));
        });

        return array_values(array_merge($testMigrations, $coreMigrations, $packageMigrations));
    }

    private function buildOrderedMigrationFilename(string $migrationPath, int $index): string
    {
        $migrationName = basename($migrationPath, '.php');
        $normalizedName = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migrationName) ?? $migrationName;

        return sprintf('2000_01_01_%06d_%s.php', $index, $normalizedName);
    }
}
