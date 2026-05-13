<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('keeps every package manifest in an approved product group', function (): void {
    $allowedProductGroups = [
        'admin' => 'Capell Admin',
        'automation' => 'Capell Automation',
        'collaboration' => 'Capell Collaboration',
        'commercial' => 'Capell Commercial',
        'communications' => 'Capell Communications',
        'content-product' => 'Capell Content',
        'form-builder' => 'Capell FormBuilder',
        'foundation' => 'Capell Foundation',
        'growth' => 'Capell Growth',
        'media' => 'Capell Media',
        'newsletter' => 'Capell Marketing',
        'operations' => 'Capell Operations',
        'publishing-pro' => 'Capell Publishing Pro',
        'search-seo' => 'Capell Search & SEO',
        'themes' => 'Capell Themes',
    ];

    $manifests = packageManifestPayloads();

    $invalid = [];

    foreach ($manifests as $path => $manifest) {
        $product = $manifest['product'] ?? [];
        $bundle = is_array($product) ? ($product['bundle'] ?? null) : null;

        if (! is_string($bundle) || ! isset($allowedProductGroups[$bundle])) {
            $invalid[$path] = 'Unknown bundle.';

            continue;
        }

        if (($product['group'] ?? null) !== $allowedProductGroups[$bundle]) {
            $invalid[$path] = 'Product group does not match bundle.';
        }

        if (! in_array($product['tier'] ?? null, ['free', 'premium'], true)) {
            $invalid[$path] = 'Tier must be free or premium.';
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
        $bundle = $manifest['product']['bundle'] ?? 'missing';
        $bundle = is_string($bundle) ? $bundle : 'missing';

        $packagesByBundle[$bundle][] = $path;
    }

    ksort($packagesByBundle);

    foreach (array_keys($packagesByBundle) as $bundle) {
        sort($packagesByBundle[$bundle]);
    }

    expect($packagesByBundle)->toBe([
        'admin' => [
            'translation-manager/capell.json',
        ],
        'automation' => [
            'public-actions/capell.json',
        ],
        'collaboration' => [
            'notes/capell.json',
        ],
        'commercial' => [
            'ai-orchestrator/capell.json',
        ],
        'communications' => [
            'email-studio/capell.json',
        ],
        'content-product' => [
            'address/capell.json',
            'events/capell.json',
        ],
        'form-builder' => [
            'form-builder/capell.json',
        ],
        'foundation' => [
            'blog/capell.json',
            'content-sections/capell.json',
            'demo-kit/capell.json',
            'foundation-theme/capell.json',
            'frontend-authoring/capell.json',
            'frontend-optimizer/capell.json',
            'hero/capell.json',
            'html-cache/capell.json',
            'media-library/capell.json',
            'navigation/capell.json',
            'tags/capell.json',
            'welcome-tour/capell.json',
        ],
        'growth' => [
            'campaign-studio/capell.json',
            'ga4-reports/capell.json',
            'insights/capell.json',
        ],
        'media' => [
            'media-ai/capell.json',
        ],
        'newsletter' => [
            'newsletter/capell.json',
        ],
        'operations' => [
            'access-gate/capell.json',
            'agent-bridge/capell.json',
            'dashboard-reports/capell.json',
            'deployments/capell.json',
            'diagnostics/capell.json',
            'login-audit/capell.json',
            'migration-assistant/capell.json',
            'password-policy/capell.json',
            'wordpress-importer/capell.json',
        ],
        'publishing-pro' => [
            'api/capell.json',
            'publishing-studio/capell.json',
        ],
        'search-seo' => [
            'search/capell.json',
            'seo-suite/capell.json',
            'site-discovery/capell.json',
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
