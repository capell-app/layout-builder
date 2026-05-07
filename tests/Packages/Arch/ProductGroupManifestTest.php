<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('keeps every package manifest in an approved product group', function (): void {
    $allowedProductGroups = [
        'foundation' => ['productGroup' => 'Capell Foundation', 'tier' => 'free'],
        'commercial' => ['productGroup' => 'Capell Commercial', 'tier' => 'premium'],
        'form-builder' => ['productGroup' => 'Capell FormBuilder', 'tier' => 'premium'],
        'publishing-pro' => ['productGroup' => 'Capell Publishing Pro', 'tier' => 'premium'],
        'operations' => ['productGroup' => 'Capell Operations', 'tier' => 'premium'],
        'growth' => ['productGroup' => 'Capell Growth', 'tier' => 'premium'],
        'search-seo' => ['productGroup' => 'Capell Search & SEO', 'tier' => 'premium'],
        'themes' => ['productGroup' => 'Capell Themes', 'tier' => 'premium'],
    ];

    $manifests = packageManifestPayloads();

    $invalid = [];

    foreach ($manifests as $path => $manifest) {
        $bundle = $manifest['bundle'] ?? null;

        if (! is_string($bundle) || ! isset($allowedProductGroups[$bundle])) {
            $invalid[$path] = 'Unknown bundle.';

            continue;
        }

        $expected = $allowedProductGroups[$bundle];

        if (($manifest['productGroup'] ?? null) !== $expected['productGroup']) {
            $invalid[$path] = 'Product group does not match bundle.';
        }

        if (($manifest['tier'] ?? null) !== $expected['tier']) {
            $invalid[$path] = 'Tier does not match bundle.';
        }
    }

    expect($invalid)->toBe(
        [],
        'Package manifests must use the approved Capell product groups: ' .
        json_encode($invalid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('groups packages into the current product bundles', function (): void {
    $packagesByBundle = [];

    foreach (packageManifestPayloads() as $path => $manifest) {
        $bundle = $manifest['bundle'] ?? 'missing';
        $bundle = is_string($bundle) ? $bundle : 'missing';

        $packagesByBundle[$bundle][] = $path;
    }

    ksort($packagesByBundle);

    foreach (array_keys($packagesByBundle) as $bundle) {
        sort($packagesByBundle[$bundle]);
    }

    expect($packagesByBundle)->toBe([
        'commercial' => [
            'ai-orchestrator/capell.json',
        ],
        'form-builder' => [
            'form-builder/capell.json',
        ],
        'foundation' => [
            'address/capell.json',
            'blog/capell.json',
            'content-sections/capell.json',
            'foundation-theme/capell.json',
            'frontend-authoring/capell.json',
            'html-optimizer/capell.json',
            'layout-builder/capell.json',
            'media-library/capell.json',
            'navigation/capell.json',
            'redirects/capell.json',
            'tags/capell.json',
        ],
        'growth' => [
            'insights/capell.json',
            'campaign-studio/capell.json',
        ],
        'operations' => [
            'login-audit/capell.json',
            'backup/capell.json',
            'deployments/capell.json',
            'diagnostics/capell.json',
            'agent-bridge/capell.json',
        ],
        'publishing-pro' => [
            'admin-preview/capell.json',
            'publishing-studio/capell.json',
        ],
        'search-seo' => [
            'seo-suite/capell.json',
            'search/capell.json',
        ],
        'themes' => [
            'theme-agency/capell.json',
            'theme-corporate/capell.json',
            'theme-saas/capell.json',
        ],
    ]);
});

/**
 * @return array<string, array<string, mixed>>
 */
function packageManifestPayloads(): array
{
    $finder = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->name('capell.json')
        ->depth('< 4');

    $payloads = [];

    foreach ($finder as $manifest) {
        $payloads[$manifest->getRelativePathname()] = json_decode(
            $manifest->getContents(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    ksort($payloads);

    return $payloads;
}
