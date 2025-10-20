<?php

declare(strict_types=1);

namespace Capell\Layout\Commands;

use Capell\Core\Actions\AddVendorAssetToThemeAction;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\Layout\Actions\InstallPackageAction;
use Capell\Layout\CapellLayoutManager;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'capell-layout:install';

    protected $description = 'Install the Capell Layout package';

    public function handle(): int
    {
        $this->info('Installing Capell Layout...');

        $this->updateThemes();

        $this->call('vendor:publish', ['--tag' => 'capell-layout-publish']);
        $this->call('vendor:publish', ['--tag' => 'capell-layout-assets']);

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => CapellLayoutManager::getMigrations(),
                '--path' => __DIR__ . '/../../database/migrations',
            ]
        );

        $this->call('migrate');

        InstallPackageAction::run();

        $this->info('Capell Layout installation complete.');

        return self::SUCCESS;
    }

    private function updateThemes(): void
    {
        $path = 'vendor/capell-layout/frontend';
        $file = 'resources/js/capell-layout.js';

        CapellCore::getModel(ModelEnum::Theme)::query()
            ->lazy()
            ->each(fn (Theme $theme) => AddVendorAssetToThemeAction::run($theme, $path, $file));
    }
}
