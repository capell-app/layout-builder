<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

use Capell\Migrator\Data\PackageManifest;
use Capell\Migrator\Enums\PackageType;

/**
 * Checks that a raw manifest array is compatible with the current Capell
 * build before we attempt to reconstruct anything from the payload.
 *
 * Validation is deliberately conservative: an unknown schema version or
 * package type is a hard fail; a newer Capell version only warns.
 */
final class ManifestValidator
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function validate(array $manifest): ManifestValidationReport
    {
        $errors = [];
        $warnings = [];

        $schemaVersion = $manifest['schema_version'] ?? null;
        if ($schemaVersion !== PackageManifest::SCHEMA_VERSION) {
            $errors[] = sprintf(
                'Unsupported manifest schema version [%s]; expected [%d].',
                var_export($schemaVersion, true),
                PackageManifest::SCHEMA_VERSION,
            );
        }

        $packageType = $manifest['package_type'] ?? null;
        if (! is_string($packageType) || PackageType::tryFrom($packageType) === null) {
            $errors[] = sprintf(
                'Unknown package type [%s].',
                is_string($packageType) ? $packageType : var_export($packageType, true),
            );
        }

        $exportedCapell = $manifest['capell_version'] ?? null;
        $app = app();
        $currentCapell = method_exists($app, 'version') ? $app->version() : null;

        if (is_string($exportedCapell) && is_string($currentCapell) && version_compare($exportedCapell, $currentCapell, '>')) {
            $warnings[] = sprintf(
                'Package was exported from Capell [%s] but this instance is [%s]; proceed with care.',
                $exportedCapell,
                $currentCapell,
            );
        }

        return new ManifestValidationReport(errors: $errors, warnings: $warnings);
    }
}
