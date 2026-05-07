<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Support;

use Capell\FrontendOptimizer\Contracts\CriticalCssGenerator;
use Capell\FrontendOptimizer\Enums\AssetKind;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Capell\FrontendOptimizer\Models\FrontendRenderProfile;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use RuntimeException;
use Symfony\Component\Process\Process;

class PlaywrightCriticalCssGenerator implements CriticalCssGenerator
{
    public function __construct(private readonly Factory $filesystems) {}

    public function generate(FrontendRenderProfile $profile, string $url): string
    {
        $criticalCssPath = $this->criticalCssPath($profile);
        $payloadPath = $this->payloadPath($profile);
        /** @var FilesystemAdapter $localDisk */
        $localDisk = $this->filesystems->disk('local');

        $localDisk->put($payloadPath, json_encode([
            'eligible_stylesheet_paths' => $this->eligibleStylesheetPaths($profile),
            'manifest' => $profile->manifest,
            'profile_hash' => $profile->hash,
            'url' => $url,
            'viewports' => config('capell-frontend-optimizer.playwright.viewports', []),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        $process = new Process([
            config('capell-frontend-optimizer.playwright.node_binary', 'node'),
            config('capell-frontend-optimizer.playwright.script'),
            $localDisk->path($payloadPath),
            $localDisk->path($criticalCssPath),
        ]);
        $process->setTimeout((float) config('capell-frontend-optimizer.playwright.timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput() . PHP_EOL . $process->getOutput());

            throw new RuntimeException($message !== '' ? $message : 'Playwright critical CSS generation failed.');
        }

        if (! $localDisk->exists($criticalCssPath)) {
            throw new RuntimeException('Playwright critical CSS generation did not create an output file.');
        }

        return $criticalCssPath;
    }

    private function criticalCssPath(FrontendRenderProfile $profile): string
    {
        $directory = trim(config('capell-frontend-optimizer.paths.critical_css', 'capell/frontend-optimizer/critical-css'), '/');

        return sprintf('%s/%s.css', $directory, $profile->hash);
    }

    private function payloadPath(FrontendRenderProfile $profile): string
    {
        return sprintf('capell/frontend-optimizer/payloads/%s.json', $profile->hash);
    }

    /** @return array<int, string> */
    private function eligibleStylesheetPaths(FrontendRenderProfile $profile): array
    {
        $assets = $profile->signature['assets'] ?? [];

        if (! is_array($assets)) {
            return [];
        }

        return collect($assets)
            ->filter(static fn (mixed $asset): bool => is_array($asset))
            ->filter(fn (array $asset): bool => $this->isCriticalEligibleStylesheet($asset))
            ->map(static fn (array $asset): string => (string) ($asset['path'] ?? ''))
            ->filter(static fn (string $path): bool => $path !== '')
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $asset */
    private function isCriticalEligibleStylesheet(array $asset): bool
    {
        if (AssetKind::tryFrom((string) ($asset['kind'] ?? '')) !== AssetKind::Css) {
            return false;
        }

        if (($asset['critical_eligible'] ?? false) === true) {
            return true;
        }

        $slot = AssetSlot::tryFrom((string) ($asset['slot'] ?? ''));
        $strategy = AssetLoadingStrategy::tryFrom((string) ($asset['loading_strategy'] ?? ''));

        if (in_array($slot, [AssetSlot::Base, AssetSlot::Head, AssetSlot::AboveFold], true)) {
            return ! in_array($strategy, [AssetLoadingStrategy::Lazy, AssetLoadingStrategy::Idle, AssetLoadingStrategy::Interaction], true);
        }

        return in_array($strategy, [AssetLoadingStrategy::Critical, AssetLoadingStrategy::Blocking, AssetLoadingStrategy::Preload], true);
    }
}
