<?php

declare(strict_types=1);

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
                $invalid[$path][] = "missing {$field}";
            }
        }

        if (($manifest['manifest-version'] ?? null) !== 3) {
            $invalid[$path][] = 'manifest-version must be 3';
        }

        foreach (['slug', 'displayName', 'capellApiVersion', 'version'] as $field) {
            if (! is_string($manifest[$field] ?? null) || $manifest[$field] === '') {
                $invalid[$path][] = "{$field} must be a non-empty string";
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
            $expected = CAPELL_MANIFEST_V3_PROVIDER_BUCKETS;
            sort($expected);

            if ($buckets !== $expected) {
                $invalid[$path][] = 'providers must include all v3 buckets';
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
                $invalid[$path][] = "performance.cacheSafety.{$field} must be boolean";
            }
        }

        foreach (['variesBy', 'invalidationSources'] as $field) {
            if (! is_array($manifest['performance']['cacheSafety'][$field] ?? null)) {
                $invalid[$path][] = "performance.cacheSafety.{$field} must be a list";
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
                $invalid[$path][] = "commercial.{$field} is required";
            }
        }
    }

    expect($invalid)->toBe(
        [],
        'Paid package manifests must expose sales and entitlement metadata: ' .
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
