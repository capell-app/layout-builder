<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Install\ConsoleProgressReporter;
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderPackageAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $description = 'Install layout builder package';

    protected $signature = 'capell:layout-builder-install';

    public function handle(): int
    {
        InstallLayoutBuilderPackageAction::run(CapellCore::getPackage('capell-app/layout-builder'), [], new ConsoleProgressReporter($this));

        $this->newLine();
        $this->info('Capell Layout Builder installed successfully.');

        return self::SUCCESS;
    }
}
