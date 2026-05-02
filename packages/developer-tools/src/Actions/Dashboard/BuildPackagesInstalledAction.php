<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Actions\Dashboard;

use Capell\DeveloperTools\Data\Dashboard\PackageInfoData;
use Capell\DeveloperTools\Data\Dashboard\PackagesInstalledData;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

/**
 * @method static PackagesInstalledData run()
 */
final class BuildPackagesInstalledAction
{
    use AsAction;

    /**
     * Maps a composer package name to its short handle, config-file name, and docs URL.
     *
     * @var array<string, array{short: string, config: string, docs: ?string}>
     */
    private const KNOWN_PACKAGES = [
        'capell-app/core' => [
            'short' => 'core',
            'config' => 'capell',
            'docs' => 'https://github.com/capell-app/capell/blob/4.x/packages/core/README.md',
        ],
        'capell-app/admin' => [
            'short' => 'admin',
            'config' => 'capell-admin',
            'docs' => 'https://github.com/capell-app/capell/blob/4.x/packages/admin/README.md',
        ],
        'capell-app/frontend' => [
            'short' => 'frontend',
            'config' => 'capell-frontend',
            'docs' => 'https://github.com/capell-app/capell/blob/4.x/packages/frontend/README.md',
        ],
        'capell-app/backup' => [
            'short' => 'backup',
            'config' => 'backup',
            'docs' => 'https://github.com/capell-app/capell/blob/4.x/packages/backup/README.md',
        ],
        'capell-app/capell-mosaic' => [
            'short' => 'mosaic',
            'config' => 'capell-mosaic',
            'docs' => 'https://github.com/capell-app/capell-packages/blob/4.x/packages/mosaic/README.md',
        ],
        'capell-app/capell-blog' => [
            'short' => 'blog',
            'config' => 'capell-blog',
            'docs' => 'https://github.com/capell-app/capell-packages/blob/4.x/packages/blog/README.md',
        ],
        'capell-app/capell-address' => [
            'short' => 'address',
            'config' => 'capell-address',
            'docs' => 'https://github.com/capell-app/capell-packages/blob/4.x/packages/address/README.md',
        ],
        'capell-app/seo-tools' => [
            'short' => 'seo-tools',
            'config' => 'capell-seo-tools',
            'docs' => 'https://github.com/capell-app/capell-packages/blob/4.x/packages/seo-tools/README.md',
        ],
    ];

    public function handle(): PackagesInstalledData
    {
        $installedJsonPath = base_path('vendor/composer/installed.json');

        if (! File::exists($installedJsonPath)) {
            return new PackagesInstalledData(
                packages: PackageInfoData::collect([], DataCollection::class),
            );
        }

        /** @var array{packages?: array<int, array{name: string, version: string}>} $installedData */
        $installedData = json_decode(File::get($installedJsonPath), true) ?? [];

        /** @var array<int, array{name: string, version: string}> $allPackages */
        $allPackages = $installedData['packages'] ?? [];

        $rows = [];

        foreach ($allPackages as $package) {
            $composerName = $package['name'] ?? '';

            if (! isset(self::KNOWN_PACKAGES[$composerName])) {
                continue;
            }

            $meta = self::KNOWN_PACKAGES[$composerName];
            $configPath = config_path($meta['config'] . '.php');

            $rows[] = new PackageInfoData(
                name: $meta['short'],
                composerName: $composerName,
                version: $package['version'] ?? 'unknown',
                configPublished: File::exists($configPath),
                configPath: $configPath,
                docsUrl: $meta['docs'],
            );
        }

        return new PackagesInstalledData(
            packages: PackageInfoData::collect($rows, DataCollection::class),
        );
    }
}
