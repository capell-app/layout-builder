<?php

declare(strict_types=1);

namespace Capell\Layout\Commands;

use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Core\Actions\AddVendorAssetToThemeAction;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\Layout\Actions\InstallPackageAction;
use Capell\Layout\CapellLayoutManager;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\LayoutModelRegistrar;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'capell-layout:install';

    protected $description = 'Install the Capell Layout package';

    public function handle(): int
    {
        $this->info('Installing Capell Layout...');

        LayoutModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $this->updateThemes();

        $this->call('vendor:publish', ['--tag' => 'capell-layout-publish']);
        $this->call('vendor:publish', ['--tag' => 'capell-layout-assets']);

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => CapellLayoutManager::getMigrations(),
                '--path' => __DIR__ . '/../../database/migrations',
            ],
        );

        $this->call('migrate');

        $this->call('filament:assets');

        InstallPackageAction::run();

        $this->info('Capell Layout installation complete.');

        return self::SUCCESS;
    }

    private function updateThemes(): void
    {
        $path = 'vendor/capell-layout/frontend';

        CapellCore::getModel(CoreModelEnum::Theme)::query()
            ->lazy()
            ->each(
                function (Theme $theme) use ($path): void {
                    $vendorAssets = $theme->meta['vendor_assets'] ?? [];
                    $removeAssets = [
                        [
                            'path' => 'vendor/capell-frontend',
                            'file' => 'resources/css/capell-frontend.css',
                        ],
                    ];

                    $filteredAssets = array_filter(
                        $vendorAssets,
                        static fn (array $asset): bool => collect($removeAssets)
                            ->doesntContain(
                                static fn (array $removeAsset): bool => $asset['path'] === $removeAsset['path']
                                && $asset['file'] === $removeAsset['file'],
                            ),
                    );

                    $theme->setAttribute('meta', array_replace(
                        $theme->meta,
                        ['vendor_assets' => array_values($filteredAssets)],
                    ));

                    AddVendorAssetToThemeAction::run(
                        $theme,
                        $path,
                        [
                            'resources/css/capell-layout.css',
                            'resources/js/capell-layout.js',
                        ],
                    );
                },
            );
    }
}
