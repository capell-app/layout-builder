<?php

declare(strict_types=1);

namespace Capell\Mosaic\Console\Commands;

use Illuminate\Console\Command;

class UpgradeCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade capell-mosaic';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell:layout-upgrade';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'capell-mosaic-assets', '--force' => true]);

        $this->newLine();
        $this->info('Capell Layout upgraded successfully.');

        return Command::SUCCESS;
    }
}
