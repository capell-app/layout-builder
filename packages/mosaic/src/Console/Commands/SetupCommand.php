<?php

declare(strict_types=1);

namespace Capell\Mosaic\Console\Commands;

use Capell\Mosaic\Actions\InstallPackageAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'capell:layout-setup';

    protected $description = 'Setting up the Capell Layout package';

    public function handle(): int
    {
        InstallPackageAction::run();

        $this->newLine();
        $this->info('Capell Layout setup successfully.');

        return self::SUCCESS;
    }
}
