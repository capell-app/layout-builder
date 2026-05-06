<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\Dashboard;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Diagnostics\Data\Dashboard\MigrationsHealthData;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static MigrationsHealthData run()
 */
final class BuildMigrationsHealthAction
{
    use AsAction;

    public function handle(): MigrationsHealthData
    {
        $lastBatch = $this->fetchLastBatch();

        $pendingMigrations = $this->detectPendingMigrations();
        $orphanedRegistrations = $this->detectOrphanedRegistrations();

        return new MigrationsHealthData(
            pendingCount: count($pendingMigrations),
            orphanedCount: count($orphanedRegistrations),
            pendingMigrations: $pendingMigrations,
            orphanedRegistrations: $orphanedRegistrations,
            lastBatch: $lastBatch,
        );
    }

    private function fetchLastBatch(): ?int
    {
        $max = DB::table('migrations')->max('batch');

        return $max !== null ? (int) $max : null;
    }

    /**
     * Use the framework migration-assistant to find truly pending migrations across all registered paths.
     *
     * @return list<string>
     */
    private function detectPendingMigrations(): array
    {
        /** @var Migrator $migrator */
        $migrator = resolve('migrator');

        $paths = array_merge([database_path('migrations')], $migrator->paths());
        $files = $migrator->getMigrationFiles($paths);
        $ran = $migrator->getRepository()->getRan();

        $pending = array_values(array_diff(array_keys($files), $ran));
        sort($pending);

        return $pending;
    }

    /**
     * Check every registration in HasMigrations lists against disk.
     * Entries with no matching file are orphaned.
     *
     * @return list<array{package: string, name: string, expectedPath: string}>
     */
    private function detectOrphanedRegistrations(): array
    {
        $base = realpath(__DIR__ . '/../../../../../../');

        $registrations = [
            [
                'package' => 'core',
                'names' => CapellCore::getMigrations(),
                'directory' => $base . '/packages/core/database/migrations',
            ],
            [
                'package' => 'core-settings',
                'names' => CapellCore::getSettingMigrations(),
                'directory' => $base . '/packages/core/database/settings',
            ],
            [
                'package' => 'admin',
                'names' => CapellAdmin::getMigrations(),
                'directory' => $base . '/packages/admin/database/migrations',
            ],
            [
                'package' => 'admin-settings',
                'names' => CapellAdmin::getSettingMigrations(),
                'directory' => $base . '/packages/admin/database/settings',
            ],
        ];

        $orphaned = [];

        foreach ($registrations as $registration) {
            foreach ($registration['names'] as $name) {
                $expectedPath = $registration['directory'] . '/' . $name . '.php';

                if (! File::exists($expectedPath)) {
                    $orphaned[] = [
                        'package' => $registration['package'],
                        'name' => $name,
                        'expectedPath' => $expectedPath,
                    ];
                }
            }
        }

        return $orphaned;
    }
}
