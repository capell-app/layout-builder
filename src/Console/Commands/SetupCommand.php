<?php

declare(strict_types=1);

namespace Capell\Mosaic\Console\Commands;

use Capell\Mosaic\Actions\InstallPackageAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'capell:mosaic-setup
        {--user= : Ignored — accepted for compatibility with capell:install}
        {--sites= : Ignored — accepted for compatibility with capell:install}
        {--languages= : Ignored — accepted for compatibility with capell:install}
        {--url= : Ignored — accepted for compatibility with capell:install}
    ';

    protected $description = 'Setting up the Capell Mosaic package';

    public function handle(): int
    {
        InstallPackageAction::run();

        $this->newLine();
        $this->info('Capell Mosaic setup successfully.');

        $this->newLine();
        $this->comment('Running hero setup...');
        $this->call('capell:hero-setup');

        return self::SUCCESS;
    }
}
