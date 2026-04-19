<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Capabilities\CapabilityRegistry;
use Capell\Plugins\Data\CapabilityWarningData;
use Capell\Plugins\Enums\CapabilityWarningLevel;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\AnystackClient;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Plugins\Support\StderrScrubber;
use Lorisleiva\Actions\Action;
use RuntimeException;
use Throwable;

final class InstallPluginAction extends Action
{
    private const STDERR_TAIL_LENGTH = 400;

    public function __construct(
        private readonly ComposerRunner $composerRunner,
        private readonly AnystackClient $anystackClient,
    ) {}

    public function handle(
        MarketplacePlugin $plugin,
        ?string $licenseKey = null,
        ?string $siteId = null,
        ?string $fingerprint = null,
    ): void {
        $isPaid = $plugin->price_once !== null
            || $plugin->price_monthly !== null
            || $plugin->price_yearly !== null;

        if ($isPaid && $licenseKey === null) {
            throw new RuntimeException(
                'Cannot install paid plugin without license key',
            );
        }

        if ($isPaid && $siteId === null) {
            throw new RuntimeException(
                'Cannot install paid plugin without siteId for license activation',
            );
        }

        $repoConfigured = false;

        if ($licenseKey !== null) {
            if ($plugin->anystack_product_id === null) {
                throw new RuntimeException(
                    'Cannot configure Anystack repository: plugin has no anystack_product_id configured',
                );
            }

            $repoUrl = $this->anystackClient->composerRepositoryUrl($plugin->anystack_product_id);

            $configResult = $this->composerRunner->configureAnystackRepo(
                $plugin->anystack_product_id,
                $licenseKey,
                $fingerprint,
            );

            if (! $configResult->successful()) {
                throw new RuntimeException(
                    "Failed to configure Anystack repository ({$repoUrl}): {$configResult->stderr}",
                );
            }

            $repoConfigured = true;

            // Activate the license against anystack BEFORE running composer require.
            // A failed activation should short-circuit the install so we don't burn
            // composer-resolve time on a license that will never work.
            if ($siteId !== null) {
                try {
                    ActivateLicenseAction::run($plugin, $licenseKey, $siteId, $fingerprint);
                } catch (Throwable $activationFailure) {
                    $this->cleanupAnystackRepo($plugin);

                    throw $activationFailure;
                }
            }
        }

        try {
            $installResult = $this->composerRunner->requirePackage(
                $plugin->composer_name,
                $plugin->latest_version,
            );
        } catch (Throwable $runnerFailure) {
            if ($repoConfigured) {
                $this->cleanupAnystackRepo($plugin);
            }

            throw $runnerFailure;
        }

        if ($installResult->successful()) {
            $plugin->auditLog()->create([
                'action' => 'installed',
                'actor_id' => auth()->id(),
                'data' => [
                    'version' => $plugin->latest_version,
                ],
                'created_at' => now(),
            ]);

            return;
        }

        $stderrTail = StderrScrubber::scrub(
            substr($installResult->stderr, -self::STDERR_TAIL_LENGTH),
            $licenseKey,
        );

        $plugin->auditLog()->create([
            'action' => 'install_failed',
            'actor_id' => auth()->id(),
            'data' => [
                'version' => $plugin->latest_version,
                'exit_code' => $installResult->exitCode,
                'stderr_tail' => $stderrTail,
            ],
            'created_at' => now(),
        ]);

        if ($repoConfigured) {
            $this->cleanupAnystackRepo($plugin);
        }

        throw new RuntimeException(
            "Plugin installation failed with exit code {$installResult->exitCode}: {$stderrTail}",
        );
    }

    public function previewCapabilityWarnings(MarketplacePlugin $plugin): CapabilityWarningData
    {
        if ($plugin->capabilities === null || count($plugin->capabilities) === 0) {
            return new CapabilityWarningData(
                highestLevel: CapabilityWarningLevel::Green,
                warnings: [],
            );
        }

        $descriptors = [];
        $warnings = [];

        foreach ($plugin->capabilities as $capabilityString) {
            try {
                $descriptor = CapabilityRegistry::parse($capabilityString);
                $descriptors[] = $descriptor;
                $warningLevelLetter = $this->getWarningLevelLetter($descriptor->warningLevel);
                $warnings[] = $warningLevelLetter . '. ' . $descriptor->title;
            } catch (RuntimeException) {
                continue;
            }
        }

        $highestLevel = CapabilityWarningLevel::Green;
        foreach ($descriptors as $descriptor) {
            if ($descriptor->warningLevel === CapabilityWarningLevel::Red) {
                $highestLevel = CapabilityWarningLevel::Red;
                break;
            }

            if ($descriptor->warningLevel === CapabilityWarningLevel::Yellow
                && $highestLevel !== CapabilityWarningLevel::Red) {
                $highestLevel = CapabilityWarningLevel::Yellow;
            }
        }

        return new CapabilityWarningData(
            highestLevel: $highestLevel,
            warnings: $warnings,
        );
    }

    /**
     * Best-effort cleanup of the http-basic auth entry + local composer
     * repository entry that we added for anystack. Failures are swallowed
     * and captured in the audit log so the primary error path (the install
     * failure itself) remains useful to callers.
     */
    private function cleanupAnystackRepo(MarketplacePlugin $plugin): void
    {
        if ($plugin->anystack_product_id === null) {
            return;
        }

        $cleanup = $this->composerRunner->removeAnystackRepo($plugin->anystack_product_id);

        if ($cleanup->successful()) {
            return;
        }

        $plugin->auditLog()->create([
            'action' => 'install_cleanup_warning',
            'actor_id' => auth()->id(),
            'data' => [
                'product_id' => $plugin->anystack_product_id,
                'exit_code' => $cleanup->exitCode,
                'stderr_tail' => StderrScrubber::scrub(
                    substr($cleanup->stderr, -self::STDERR_TAIL_LENGTH),
                    null,
                ),
            ],
            'created_at' => now(),
        ]);
    }

    private function getWarningLevelLetter(CapabilityWarningLevel $level): string
    {
        return match ($level) {
            CapabilityWarningLevel::Red => 'R',
            CapabilityWarningLevel::Yellow => 'Y',
            CapabilityWarningLevel::Green => 'G',
        };
    }
}
