<?php

declare(strict_types=1);

require_once __DIR__ . '/../../scripts/audit-manifest-v3.php';

it('traces provider-discovered contribution types in each manifest', function (): void {
    $audit = capell_manifest_v3_audit(dirname(__DIR__, 2));
    $missing = [];

    foreach ($audit['packages'] as $slug => $package) {
        if ($package['missingContributionTypes'] !== []) {
            $missing[$slug] = $package['missingContributionTypes'];
        }
    }

    expect($missing)->toBe(
        [],
        'Provider-discovered contribution types must be declared in contributes or deferred in contributionTraceability.deferredContributions: ' .
        json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('keeps manifest contribution rows structurally valid', function (): void {
    $invalid = [];

    foreach (capell_manifest_v3_manifest_payloads(dirname(__DIR__, 2)) as $path => $manifest) {
        foreach (($manifest['contributes'] ?? []) as $index => $contribution) {
            if (! is_array($contribution)) {
                $invalid[$path][] = "contributes.{$index} must be an object";

                continue;
            }

            foreach (['type', 'class'] as $field) {
                if (! is_string($contribution[$field] ?? null) || $contribution[$field] === '') {
                    $invalid[$path][] = "contributes.{$index}.{$field} must be a non-empty string";
                }
            }
        }

        foreach (capell_manifest_v3_deferred_contribution_types($manifest) as $type) {
            if (! array_key_exists($type, CAPELL_MANIFEST_V3_CONTRIBUTION_PATTERNS)) {
                $invalid[$path][] = "contributionTraceability.deferredContributions contains unknown type {$type}";
            }
        }
    }

    expect($invalid)->toBe(
        [],
        'Contribution rows must be typed and traceable: ' .
        json_encode($invalid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});
