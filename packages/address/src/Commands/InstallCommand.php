<?php

declare(strict_types=1);

namespace Capell\Address\Commands;

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
    protected $signature = 'capell-address:install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Capell Address...');

        $this->call(
            'capell:publish-migrations',
            [
                '--items' => [
                    'create_countries_table',
                    'create_addresses_table',
                ],
                '--path' => __DIR__ . '/../../database/migrations',
            ]
        );

        $this->call('migrate');

        $this->info('Capell Address installation complete.');

        return self::SUCCESS;
    }
}
