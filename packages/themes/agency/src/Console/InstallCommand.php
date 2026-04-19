<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Console;

use Capell\Themes\Agency\Actions\InstallAgencyThemeAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'agency:install
        {--force : Overwrite existing theme record}
        {--seed-layouts : Seed pre-built Mosaic layouts}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Capell Agency theme (creates theme record and seeds optional layouts).';

    public function handle(InstallAgencyThemeAction $action): int
    {
        $this->info('Installing Capell Agency theme...');

        $result = $action->handle([
            'force' => (bool) $this->option('force'),
            'seed_layouts' => (bool) $this->option('seed-layouts'),
        ]);

        $this->info('Agency theme installed: '.($result['theme_key'] ?? 'agency'));

        return self::SUCCESS;
    }
}
