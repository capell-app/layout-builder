<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Core\Support\Migration\MigrationFilesystemInterface;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutModelRegistrar;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'capell:layout-builder-install';

    protected $description = 'Install the Capell LayoutBuilder package';

    public function __construct(private readonly MigrationFilesystemInterface $fileManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        LayoutModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $this->call('vendor:publish', ['--tag' => 'capell-layout-builder-assets', '--force' => true]);

        $migrations = __DIR__ . '/../../../database/migrations';
        if (! $this->fileManager->isDir($migrations)) {
            $this->error('Migrations directory does not exist.');

            return Command::FAILURE;
        }

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => CapellLayoutManager::getMigrations(),
                '--path' => $migrations,
            ],
        );

        $this->call('migrate');

        $this->callSilent('filament:assets');

        $this->newLine();
        $this->info('Capell LayoutBuilder installed successfully.');

        return self::SUCCESS;
    }
}
