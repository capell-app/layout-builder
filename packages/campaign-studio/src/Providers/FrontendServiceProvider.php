<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Providers;

use Capell\Core\Facades\CapellCore;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class FrontendServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        Blade::anonymousComponentNamespace('Capell\\CampaignStudio\\View\\Components');
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(CampaignStudioServiceProvider::$packageName);
    }
}
