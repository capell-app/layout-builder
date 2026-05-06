<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\LayoutBuilder\Actions\InstallPackageAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'capell:layout-builder-setup
        {--user= : Ignored — accepted for compatibility with capell:install}
        {--sites= : Ignored — accepted for compatibility with capell:install}
        {--languages= : Ignored — accepted for compatibility with capell:install}
        {--url= : Ignored — accepted for compatibility with capell:install}
    ';

    protected $description = 'Setting up the Capell LayoutBuilder package';

    public function handle(): int
    {
        InstallPackageAction::run();

        $this->newLine();
        $this->info('Capell LayoutBuilder setup successfully.');

        $this->newLine();
        $this->comment('Running hero setup...');
        $this->call('capell:hero-setup');

        return self::SUCCESS;
    }
}
