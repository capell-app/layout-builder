<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Actions\InstallPackageAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Setup blog package';

    protected $signature = 'capell:blog-setup
        {--user= : Ignored — accepted for compatibility with capell:install}
        {--sites= : Ignored — accepted for compatibility with capell:install}
        {--languages= : Ignored — accepted for compatibility with capell:install}
        {--url= : Ignored — accepted for compatibility with capell:install}
    ';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        InstallPackageAction::run();

        $this->newLine();
        $this->info('Capell Blog setup successfully.');

        return self::SUCCESS;
    }
}
