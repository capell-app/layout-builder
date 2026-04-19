<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Console;

use Capell\Themes\Corporate\Actions\InstallCorporateThemeAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'corporate:install
        {--force : Overwrite existing theme record}
        {--seed-layouts : Seed pre-built Mosaic layouts}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Capell Corporate theme (creates theme record and seeds optional layouts).';

    public function handle(InstallCorporateThemeAction $action): int
    {
        $this->info('Installing Capell Corporate theme...');

        $result = $action->handle([
            'force' => (bool) $this->option('force'),
            'seed_layouts' => (bool) $this->option('seed-layouts'),
        ]);

        $this->info('Corporate theme installed: '.($result['theme_key'] ?? 'corporate'));

        return self::SUCCESS;
    }
}
