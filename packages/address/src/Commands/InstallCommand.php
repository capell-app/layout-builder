<?php

declare(strict_types=1);

namespace Capell\Address\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Address\AddressModelRegistrar;
use Capell\Address\Enums\ModelEnum;
use Capell\Address\Enums\ResourceEnum;
use Capell\Admin\Actions\AssignPermissionsToRole;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

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
    protected $signature = 'capell-address:install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Capell Address...');

        AddressModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum) => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: ResourceEnum::cases());

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => [
                    'create_countries_table',
                    'create_addresses_table',
                ],
                '--path' => __DIR__ . '/../../database/migrations',
            ],
        );

        $this->call('migrate');

        $this->info('Capell Address installation complete.');

        return self::SUCCESS;
    }
}
