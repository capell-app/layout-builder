<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Plugins\Support\StderrScrubber;
use Lorisleiva\Actions\Action;
use RuntimeException;

final class UpdatePluginAction extends Action
{
    public function __construct(
        private readonly ComposerRunner $composerRunner,
    ) {}

    public function handle(MarketplacePlugin $plugin): void
    {
        $updateResult = $this->composerRunner->updatePackage($plugin->composer_name);

        if ($updateResult->successful()) {
            $plugin->auditLog()->create([
                'action' => 'updated',
                'actor_id' => auth()->id(),
                'data' => [],
                'created_at' => now(),
            ]);
        } else {
            $stderrTail = StderrScrubber::scrub(
                substr($updateResult->stderr, -400),
                null,
            );

            $plugin->auditLog()->create([
                'action' => 'update_failed',
                'actor_id' => auth()->id(),
                'data' => [
                    'exit_code' => $updateResult->exitCode,
                    'stderr_tail' => $stderrTail,
                ],
                'created_at' => now(),
            ]);

            throw new RuntimeException(
                sprintf('Plugin update failed with exit code %d: %s', $updateResult->exitCode, $stderrTail),
            );
        }
    }
}
