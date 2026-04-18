<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerRunner;
use Lorisleiva\Actions\Action;
use RuntimeException;

final class UninstallPluginAction extends Action
{
    public function __construct(
        private readonly ComposerRunner $composerRunner,
    ) {}

    public function handle(MarketplacePlugin $plugin): void
    {
        $uninstallResult = $this->composerRunner->removePackage($plugin->composer_name);

        if ($uninstallResult->successful()) {
            $plugin->auditLog()->create([
                'action' => 'uninstalled',
                'actor_id' => auth()->id(),
                'data' => [],
                'created_at' => now(),
            ]);
        } else {
            $stderrTail = substr($uninstallResult->stderr, -400);

            $plugin->auditLog()->create([
                'action' => 'uninstall_failed',
                'actor_id' => auth()->id(),
                'data' => [
                    'exit_code' => $uninstallResult->exitCode,
                    'stderr_tail' => $stderrTail,
                ],
                'created_at' => now(),
            ]);

            throw new RuntimeException(
                "Plugin uninstallation failed with exit code {$uninstallResult->exitCode}: {$stderrTail}",
            );
        }
    }
}
