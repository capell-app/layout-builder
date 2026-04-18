<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Support\AddressModelRegistrar;
use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Core\Support\Migration\MigrationFilesystemInterface;
use Filament\Facades\Filament;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts address tables';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell:address-install';

    public function __construct(private readonly MigrationFilesystemInterface $fileManager)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        AddressModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $migrations = __DIR__ . '/../../../database/migrations';
        if (! $this->fileManager->isDir($migrations)) {
            $this->error('Migrations directory does not exist.');

            return Command::FAILURE;
        }

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => [
                    'create_countries_table',
                    'create_addresses_table',
                ],
                '--path' => $migrations,
            ],
        );

        $this->call('migrate');

        $this->callSilent('filament:assets');

        $this->newLine();
        $this->info('Capell Address installed successfully.');

        return self::SUCCESS;
    }
}
