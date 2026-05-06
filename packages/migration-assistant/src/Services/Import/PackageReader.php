<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import;

use Capell\MigrationAssistant\Support\ChecksumGenerator;
use RuntimeException;
use ZipArchive;

/**
 * Opens a capell-cms content package, verifies integrity, and exposes
 * manifest + payload entries to downstream importers.
 *
 * Integrity policy: every file listed in integrity.json must exist in the
 * archive and match its declared checksum. Missing or tampered entries
 * abort the read.
 */
final class PackageReader
{
    public function read(string $archivePath): PackageReadResult
    {
        if (! is_file($archivePath)) {
            throw new RuntimeException(sprintf('Package archive [%s] does not exist.', $archivePath));
        }

        $archive = new ZipArchive;
        $openResult = $archive->open($archivePath, ZipArchive::RDONLY);
        if ($openResult !== true) {
            throw new RuntimeException(sprintf(
                'Failed to open package archive [%s] for reading (code %d).',
                $archivePath,
                $openResult,
            ));
        }

        try {
            $manifest = $this->readJson($archive, 'manifest.json');
            $integrity = $this->readJson($archive, 'integrity.json');

            /** @var array<string, string> $fileChecksums */
            $fileChecksums = is_array($integrity['files'] ?? null) ? $integrity['files'] : [];

            $payload = [];
            $totalUncompressedBytes = 0;

            foreach ($fileChecksums as $entryPath => $expectedChecksum) {
                $stat = $archive->statName($entryPath);
                if ($stat === false) {
                    throw new RuntimeException(sprintf(
                        'Integrity entry [%s] is missing from archive.',
                        $entryPath,
                    ));
                }

                $totalUncompressedBytes += $this->entrySize($stat);
                $this->assertPackageWithinSizeLimit($totalUncompressedBytes);

                if ($this->isMediaEntry($entryPath)) {
                    $this->assertMediaEntryWithinSizeLimit($entryPath, $stat);
                }

                $contents = null;
                if (str_starts_with($entryPath, 'pages/') || str_starts_with($entryPath, 'relations/')) {
                    $this->assertJsonEntryWithinSizeLimit($entryPath, $stat);

                    $contents = $archive->getFromName($entryPath);
                    if ($contents === false) {
                        throw new RuntimeException(sprintf(
                            'Integrity entry [%s] is missing from archive.',
                            $entryPath,
                        ));
                    }

                    $actual = ChecksumGenerator::forString($contents);
                } else {
                    $actual = $this->checksumEntry($archive, $entryPath);
                }

                if ($actual !== $expectedChecksum) {
                    throw new RuntimeException(sprintf(
                        'Integrity checksum mismatch for [%s] (expected %s, got %s).',
                        $entryPath,
                        $expectedChecksum,
                        $actual,
                    ));
                }

                if ($contents !== null) {
                    $payload[$entryPath] = $contents;
                }
            }
        } finally {
            $archive->close();
        }

        return new PackageReadResult(
            archivePath: $archivePath,
            manifest: $manifest,
            integrity: $integrity,
            payload: $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(ZipArchive $archive, string $entry): array
    {
        $stat = $archive->statName($entry);
        if ($stat === false) {
            throw new RuntimeException(sprintf('Required archive entry [%s] is missing.', $entry));
        }

        $this->assertMetadataJsonEntryWithinSizeLimit($entry, $stat);

        $contents = $archive->getFromName($entry);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Required archive entry [%s] is missing.', $entry));
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function isMediaEntry(string $entryPath): bool
    {
        return str_starts_with($entryPath, 'media/');
    }

    /**
     * @param  array<string, mixed>  $stat
     */
    private function assertMetadataJsonEntryWithinSizeLimit(string $entryPath, array $stat): void
    {
        $maxBytes = $this->configuredByteLimit('migration-assistant.limits.max_metadata_json_bytes', 1024 * 1024);

        if ($maxBytes <= 0) {
            return;
        }

        $size = $this->entrySize($stat);
        if ($size > $maxBytes) {
            throw new RuntimeException(sprintf('Metadata JSON [%s] exceeds the maximum metadata JSON size.', $entryPath));
        }
    }

    /**
     * @param  array<string, mixed>  $stat
     */
    private function assertJsonEntryWithinSizeLimit(string $entryPath, array $stat): void
    {
        $maxBytes = $this->configuredByteLimit('migration-assistant.limits.max_payload_json_bytes', 5 * 1024 * 1024);

        if ($maxBytes <= 0) {
            return;
        }

        $size = $this->entrySize($stat);
        if ($size > $maxBytes) {
            throw new RuntimeException(sprintf('JSON payload [%s] exceeds the maximum JSON payload size.', $entryPath));
        }
    }

    /**
     * @param  array<string, mixed>  $stat
     */
    private function assertMediaEntryWithinSizeLimit(string $entryPath, array $stat): void
    {
        $maxBytes = $this->configuredByteLimit('migration-assistant.limits.max_media_bytes', 50 * 1024 * 1024);

        if ($maxBytes <= 0) {
            return;
        }

        $size = $this->entrySize($stat);
        if ($size > $maxBytes) {
            throw new RuntimeException(sprintf('Media binary [%s] exceeds the maximum import size.', $entryPath));
        }
    }

    private function assertPackageWithinSizeLimit(int $totalUncompressedBytes): void
    {
        $maxBytes = $this->configuredByteLimit('migration-assistant.limits.max_package_uncompressed_bytes', 250 * 1024 * 1024);

        if ($maxBytes <= 0) {
            return;
        }

        throw_if($totalUncompressedBytes > $maxBytes, RuntimeException::class, 'Package archive exceeds the maximum package size.');
    }

    private function configuredByteLimit(string $key, int $default): int
    {
        $maxBytesConfig = config($key, $default);

        return is_numeric($maxBytesConfig) ? $maxBytesConfig : $default;
    }

    /**
     * @param  array<string, mixed>  $stat
     */
    private function entrySize(array $stat): int
    {
        $size = $stat['size'] ?? 0;

        return is_numeric($size) ? (int) $size : 0;
    }

    private function checksumEntry(ZipArchive $archive, string $entryPath): string
    {
        $stream = $archive->getStream($entryPath);
        if ($stream === false) {
            throw new RuntimeException(sprintf(
                'Integrity entry [%s] is missing from archive.',
                $entryPath,
            ));
        }

        $context = hash_init('sha256');
        while (! feof($stream)) {
            $chunk = fread($stream, 1024 * 1024);
            if ($chunk === false) {
                fclose($stream);
                throw new RuntimeException(sprintf('Failed to read archive entry [%s].', $entryPath));
            }

            hash_update($context, $chunk);
        }

        fclose($stream);

        return 'sha256-' . hash_final($context);
    }
}
