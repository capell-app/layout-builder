<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import;

/**
 * Verified content package: manifest + integrity metadata plus the JSON
 * payload entries that the importer needs (pages/*, relations/**).
 * Media binaries stay inside the archive; importers re-open the ZIP
 * by path when they need to stream bytes.
 */
final readonly class PackageReadResult
{
    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $integrity
     * @param  array<string, string>  $payload  archive path => JSON contents
     */
    public function __construct(
        public string $archivePath,
        public array $manifest,
        public array $integrity,
        public array $payload,
    ) {}
}
