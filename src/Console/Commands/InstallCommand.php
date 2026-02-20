<?php

declare(strict_types=1);

namespace Capell\Layout\Console\Commands;

use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Core\Support\Migration\MigrationFileManagerInterface;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Support\CapellLayoutManager;
use Capell\Layout\Support\LayoutModelRegistrar;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'capell:layout-install';

    protected $description = 'Install the Capell Layout package';

    public function __construct(private readonly MigrationFileManagerInterface $fileManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        LayoutModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $this->call('vendor:publish', ['--tag' => 'capell-layout-publish']);

        $this->call('vendor:publish', ['--tag' => 'capell-layout-assets', '--force' => true]);

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
        $this->info('Capell Layout installed successfully.');

        return self::SUCCESS;
    }
}
