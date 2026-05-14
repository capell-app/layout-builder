<?php

declare(strict_types=1);

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

require_once __DIR__ . '/../../scripts/audit-manifest-v3.php';

it('assigns every package directory to exactly one manifest migration group', function (): void {
    $root = dirname(__DIR__, 2);
    $audit = capell_manifest_v3_audit($root);

    expect($audit['unassignedPackages'])->toBe([]);
    expect($audit['duplicateAssignments'])->toBe([]);
});

it('keeps every package on manifest v3 with the required contract fields', function (): void {
    $manifests = capell_package_manifest_payloads();

    expect($manifests)->not->toBeEmpty();

    $invalid = [];

    foreach ($manifests as $path => $manifest) {
        foreach (CAPELL_MANIFEST_V3_REQUIRED_ROOT_FIELDS as $field) {
            if (! array_key_exists($field, $manifest)) {
                $invalid[$path][] = 'missing ' . $field;
            }
        }

        if (($manifest['manifest-version'] ?? null) !== 3) {
            $invalid[$path][] = 'manifest-version must be 3';
        }

        foreach (['slug', 'displayName', 'capellApiVersion', 'version'] as $field) {
            if (! is_string($manifest[$field] ?? null) || $manifest[$field] === '') {
                $invalid[$path][] = $field . ' must be a non-empty string';
            }
        }

        if (! is_array($manifest['product'] ?? null)) {
            $invalid[$path][] = 'product must be an object';
        }

        if (! is_array($manifest['providers'] ?? null)) {
            $invalid[$path][] = 'providers must be an object';
        } else {
            $buckets = array_keys($manifest['providers']);
            sort($buckets);
            $allowed = [
                ...CAPELL_MANIFEST_V3_PROVIDER_BUCKETS,
                ...CAPELL_MANIFEST_V3_OPTIONAL_PROVIDER_BUCKETS,
            ];
            sort($allowed);

            $missing = array_values(array_diff(CAPELL_MANIFEST_V3_PROVIDER_BUCKETS, $buckets));
            $unexpected = array_values(array_diff($buckets, $allowed));

            if ($missing !== [] || $unexpected !== []) {
                $invalid[$path][] = 'providers must include all required v3 buckets and only approved optional buckets';
            }
        }

        if (! is_array($manifest['contributes'] ?? null) || ! array_is_list($manifest['contributes'])) {
            $invalid[$path][] = 'contributes must be a list';
        }

        if (! is_array($manifest['performance']['cacheSafety'] ?? null)) {
            $invalid[$path][] = 'performance.cacheSafety must be present';
        }

        foreach (['cacheable', 'sensitiveOutput', 'queueInvalidation'] as $field) {
            if (! is_bool($manifest['performance']['cacheSafety'][$field] ?? null)) {
                $invalid[$path][] = sprintf('performance.cacheSafety.%s must be boolean', $field);
            }
        }

        foreach (['variesBy', 'invalidationSources'] as $field) {
            if (! is_array($manifest['performance']['cacheSafety'][$field] ?? null)) {
                $invalid[$path][] = sprintf('performance.cacheSafety.%s must be a list', $field);
            }
        }

        if (! is_array($manifest['commercial'] ?? null)) {
            $invalid[$path][] = 'commercial must be present';
        }

        if (! is_array($manifest['marketplace'] ?? null) || ! is_string($manifest['marketplace']['summary'] ?? null)) {
            $invalid[$path][] = 'marketplace summary must be present';
        }
    }

    expect($invalid)->toBe(
        [],
        'Package manifests must satisfy the v3 contract: ' .
        json_encode($invalid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('declares commercial intent and capabilities for paid packages', function (): void {
    $invalid = [];

    foreach (capell_package_manifest_payloads() as $path => $manifest) {
        $tier = $manifest['product']['tier'] ?? null;

        if ($tier === 'free') {
            continue;
        }

        if (($manifest['capabilities'] ?? []) === []) {
            $invalid[$path][] = 'paid packages must declare at least one capability';
        }

        foreach (['proposedLicense', 'requestedCertification', 'supportPolicy', 'privateDocsRequested'] as $field) {
            if (! array_key_exists($field, $manifest['commercial'] ?? [])) {
                $invalid[$path][] = sprintf('commercial.%s is required', $field);
            }
        }
    }

    expect($invalid)->toBe(
        [],
        'Paid package manifests must expose sales and entitlement metadata: ' .
        json_encode($invalid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('declares class-backed package health checks for diagnostics', function (): void {
    $invalid = [];

    foreach (capell_package_manifest_payloads() as $path => $manifest) {
        $healthChecks = $manifest['healthChecks'] ?? null;

        if (! is_array($healthChecks) || $healthChecks === [] || ! array_is_list($healthChecks)) {
            $invalid[$path][] = 'healthChecks must be a non-empty list';

            continue;
        }

        foreach ($healthChecks as $index => $healthCheck) {
            if (! is_array($healthCheck)) {
                $invalid[$path][] = sprintf('healthChecks.%d must be an object', $index);

                continue;
            }

            foreach (['key', 'label', 'class', 'severity', 'surface'] as $field) {
                if (! is_string($healthCheck[$field] ?? null) || $healthCheck[$field] === '') {
                    $invalid[$path][] = sprintf('healthChecks.%d.%s must be a non-empty string', $index, $field);
                }
            }

            $class = $healthCheck['class'] ?? null;

            if (is_string($class) && $class !== '') {
                if (! class_exists($class)) {
                    $invalid[$path][] = sprintf('healthChecks.%d.class must exist [%s]', $index, $class);
                } elseif (! is_subclass_of($class, ChecksExtensionHealth::class)) {
                    $invalid[$path][] = sprintf('healthChecks.%d.class must implement ChecksExtensionHealth [%s]', $index, $class);
                }
            }
        }
    }

    expect($invalid)->toBe(
        [],
        'Package manifests must expose class-backed health checks for Diagnostics: ' .
        json_encode($invalid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

/**
 * @return array<string, array<string, mixed>>
 */
function capell_package_manifest_payloads(): array
{
    return capell_manifest_v3_manifest_payloads(dirname(__DIR__, 2));
}
