<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\FoundationTheme\Actions\InstallFoundationThemeLayoutDefaultsAction;
use Illuminate\Console\Command;

final class SetupCommand extends Command
{
    protected $signature = 'capell:foundation-theme-setup {--force : Rebuild Foundation-managed layout defaults}';

    protected $description = 'Install Foundation theme layout defaults.';

    public function handle(): int
    {
        $result = InstallFoundationThemeLayoutDefaultsAction::run((bool) $this->option('force'));

        $this->components->info(sprintf(
            'Foundation theme layout defaults installed. Created: %d, updated: %d, skipped: %d.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
