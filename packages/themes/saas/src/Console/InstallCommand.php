<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Console;

use Capell\Themes\Saas\Actions\InstallSaasThemeAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'saas:install
        {--force : Overwrite existing theme record}
        {--seed-layouts : Seed pre-built Mosaic layouts}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Capell SaaS theme (creates theme record and seeds optional layouts).';

    public function handle(InstallSaasThemeAction $action): int
    {
        $this->info('Installing Capell SaaS theme...');

        $result = $action->handle([
            'force' => (bool) $this->option('force'),
            'seed_layouts' => (bool) $this->option('seed-layouts'),
        ]);

        $this->info('SaaS theme installed: '.($result['theme_key'] ?? 'saas'));

        return self::SUCCESS;
    }
}
