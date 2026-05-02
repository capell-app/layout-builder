<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Listeners;

use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Illuminate\Support\Facades\Artisan;

class RunTailwindAssetsOnPackageChange
{
    public function handleInstalled(PackageInstalled $event): void
    {
        $this->run();
    }

    public function handleUninstalled(PackageUninstalled $event): void
    {
        $this->run();
    }

    /**
     * @param  array<string,mixed>  $arguments
     */
    private function run(array $arguments = []): void
    {
        Artisan::call('capell:frontend-tailwind-assets', $arguments);
    }
}
