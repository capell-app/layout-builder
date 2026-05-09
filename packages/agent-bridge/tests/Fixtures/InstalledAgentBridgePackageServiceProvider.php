<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Tests\Fixtures;

use Capell\AgentBridge\Providers\AgentBridgeServiceProvider;
use Capell\Core\Facades\CapellCore;
use Illuminate\Support\ServiceProvider;

final class InstalledAgentBridgePackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (class_exists(CapellCore::class)) {
            CapellCore::forcePackageInstalled(AgentBridgeServiceProvider::$packageName);
        }
    }
}
