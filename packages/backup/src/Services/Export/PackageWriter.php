<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Export;

use Capell\Backup\Data\DependencyGraph;
use Capell\Backup\Data\PackageManifest;
use Capell\Backup\Support\ChecksumGenerator;
use RuntimeException;
use ZipArchive;

/**
 * Writes a serialized payload + media binaries + manifest + integrity file
 * into a deterministic ZIP archive at $destinationPath. The archive is the
 * unit the Recovery Center importer consumes.
 */
final class PackageWriter
{
    /**
     * @param  array<string, string>  $payload  archive path => JSON contents
     * @param  array<string, array{path: string, checksum: string}>  $media  ref => descriptor
     */
    public function write(
        string $destinationPath,
        PackageManifest $manifest,
        DependencyGraph $graph,
        array $payload,
        array $media,
    ): PackageManifest {
        $directory = dirname($destinationPath);
        if (! is_dir($directory) && ! @mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create export directory [%s].', $directory));
        }

        $integrity = $this->computeIntegrity($payload, $media);
        $manifest = $manifest->withChecksums([
            'payload' => ChecksumGenerator::forString(implode('', $payload)),
            'media' => $this->mediaAggregateChecksum($media),
        ]);

        $archive = new ZipArchive;
        $openResult = $archive->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new RuntimeException(sprintf(
                'Failed to open zip archive [%s] for writing (code %d).',
                $destinationPath,
                $openResult,
            ));
        }

        ksort($payload);

        foreach ($payload as $archivePath => $contents) {
            $archive->addFromString($archivePath, $contents);
        }

        foreach ($media as $descriptor) {
            $extension = pathinfo($descriptor['path'], PATHINFO_EXTENSION);
            $archivePath = sprintf(
                'media/%s%s',
                substr($descriptor['checksum'], strlen('sha256-')),
                $extension === '' ? '' : '.' . $extension,
            );

            $archive->addFile($descriptor['path'], $archivePath);
        }

        $archive->addFromString(
            'integrity.json',
            json_encode($integrity, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );

        $archive->addFromString(
            'manifest.json',
            json_encode($manifest->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );

        if (! $archive->close()) {
            throw new RuntimeException(sprintf('Failed to close zip archive [%s].', $destinationPath));
        }

        unset($graph);

        return $manifest;
    }

    /**
     * @param  array<string, string>  $payload
     * @param  array<string, array{path: string, checksum: string}>  $media
     * @return array<string, array<string, string>>
     */
    private function computeIntegrity(array $payload, array $media): array
    {
        $files = [];

        foreach ($payload as $archivePath => $contents) {
            $files[$archivePath] = ChecksumGenerator::forString($contents);
        }

        foreach ($media as $descriptor) {
            $archivePath = $this->mediaArchivePath($descriptor);
            $files[$archivePath] = $descriptor['checksum'];
        }

        return ['files' => $files];
    }

    /**
     * @param  array{path: string, checksum: string}  $descriptor
     */
    private function mediaArchivePath(array $descriptor): string
    {
        $extension = pathinfo($descriptor['path'], PATHINFO_EXTENSION);

        return sprintf(
            'media/%s%s',
            substr($descriptor['checksum'], strlen('sha256-')),
            $extension === '' ? '' : '.' . $extension,
        );
    }

    /**
     * @param  array<string, array{path: string, checksum: string}>  $media
     */
    private function mediaAggregateChecksum(array $media): string
    {
        if ($media === []) {
            return 'sha256-' . hash('sha256', '');
        }

        $checksums = array_map(static fn (array $descriptor): string => $descriptor['checksum'], $media);
        sort($checksums);

        return ChecksumGenerator::forString(implode('', $checksums));
    }
}
