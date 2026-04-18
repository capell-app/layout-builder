<?php

declare(strict_types=1);

namespace Capell\Plugins\Actions;

use Capell\Plugins\Capabilities\CapabilityRegistry;
use Capell\Plugins\Data\CapabilityWarningData;
use Capell\Plugins\Enums\CapabilityWarningLevel;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerRunner;
use Lorisleiva\Actions\Action;
use RuntimeException;

final class InstallPluginAction extends Action
{
    public function __construct(
        private readonly ComposerRunner $composerRunner,
    ) {}

    public function handle(MarketplacePlugin $plugin, ?string $licenseKey = null): void
    {
        // Check if paid plugin requires license
        if ($plugin->price_once !== null || $plugin->price_monthly !== null || $plugin->price_yearly !== null) {
            if ($licenseKey === null) {
                throw new RuntimeException(
                    'Cannot install paid plugin without license key',
                );
            }
        }

        // Configure Anystack repository if license key provided
        if ($licenseKey !== null) {
            $repoUrl = app(ComposerRunner::class)->composerRepositoryUrl($plugin->vendor);
            $configResult = $this->composerRunner->configureAnystackRepo(
                $repoUrl,
                $plugin->vendor,
                $licenseKey,
            );

            if (! $configResult->successful()) {
                throw new RuntimeException(
                    "Failed to configure Anystack repository: {$configResult->stderr}",
                );
            }
        }

        // Run composer require
        $installResult = $this->composerRunner->requirePackage(
            $plugin->composer_name,
            $plugin->latest_version,
        );

        if ($installResult->successful()) {
            $plugin->auditLog()->create([
                'action' => 'installed',
                'actor_id' => auth()->id(),
                'data' => [
                    'version' => $plugin->latest_version,
                ],
                'created_at' => now(),
            ]);
        } else {
            // Log failure with stderr tail (last 400 chars)
            $stderrTail = substr($installResult->stderr, -400);

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

            throw new RuntimeException(
                "Plugin installation failed with exit code {$installResult->exitCode}: {$stderrTail}",
            );
        }
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
                $warnings[] = strtoupper(substr($descriptor->warningLevel->value, 0, 1)) . '. ' . $descriptor->title;
            } catch (RuntimeException) {
                // Skip invalid capability strings
                continue;
            }
        }

        // Find highest warning level (Red > Yellow > Green)
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
}
