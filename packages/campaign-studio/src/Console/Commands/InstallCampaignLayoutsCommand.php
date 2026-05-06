<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Console\Commands;

use Capell\CampaignStudio\Actions\InstallCampaignLayoutsAction;
use Illuminate\Console\Command;

final class InstallCampaignLayoutsCommand extends Command
{
    protected $signature = 'capell:campaign-studio-install-layouts {--force : Update existing campaign layouts}';

    protected $description = 'Install campaign-focused LayoutBuilder layout presets.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $result = InstallCampaignLayoutsAction::run($force);

        $this->components->info(sprintf(
            'Campaign layouts installed. Created: %d, updated: %d, skipped: %d.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
