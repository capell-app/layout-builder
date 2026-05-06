<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Illuminate\Console\Command;

class UpgradeCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade capell-layout-builder';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell:layout-builder-upgrade';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'capell-layout-builder-assets', '--force' => true]);

        $this->newLine();
        $this->info('Capell LayoutBuilder upgraded successfully.');

        return Command::SUCCESS;
    }
}
