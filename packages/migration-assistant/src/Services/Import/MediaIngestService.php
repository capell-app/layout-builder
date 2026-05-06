<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import;

use Capell\Core\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Copies media binaries from an incoming content package onto the local
 * media disk and creates a matching Media row when the incoming ref has
 * no local match. Called outside the PageImportService transaction so
 * the filesystem side-effect is safe to retry; idempotency is keyed on
 * the sha256 checksum stored on the Media row's custom_properties.
 *
 * The caller supplies a temporary Model owner for the fresh Media row.
 * PageImportService rebinds the row to the final imported Page once
 * page IDs are known.
 */
final class MediaIngestService
{
    /**
     * @param  array<string, mixed>  $descriptor  shape: ref, checksum (sha256-hex), file_name, mime_type, size?, collection_name?
     * @return int|string the local media id (existing or newly created)
     */
    public function ingest(string $archivePath, array $descriptor, Model $temporaryOwner): int|string
    {
        $checksum = is_string($descriptor['checksum'] ?? null) ? $descriptor['checksum'] : '';
        throw_if($checksum === '' || ! str_starts_with($checksum, 'sha256-'), RuntimeException::class, 'Media descriptor is missing a sha256 checksum.');

        $fileName = is_string($descriptor['file_name'] ?? null) ? $descriptor['file_name'] : '';
        throw_if($fileName === '', RuntimeException::class, 'Media descriptor is missing a file_name.');

        $existing = Media::query()
            ->where('custom_properties->checksum', $checksum)
            ->first();
        if ($existing instanceof Media) {
            return $existing->getKey();
        }

        $hex = substr($checksum, strlen('sha256-'));
        throw_unless(strlen($hex) === 64 && ctype_xdigit($hex), RuntimeException::class, 'Media descriptor checksum must be a sha256 hex digest.');

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $entryPath = sprintf('media/%s%s', $hex, $extension === '' ? '' : '.' . $extension);

        $archive = new ZipArchive;
        $openResult = $archive->open($archivePath, ZipArchive::RDONLY);
        if ($openResult !== true) {
            throw new RuntimeException(sprintf('Failed to open package archive [%s].', $archivePath));
        }

        $mediaStream = null;

        try {
            [$mediaStream, $size] = $this->verifiedMediaStream($archive, $entryPath, $checksum);
        } finally {
            $archive->close();
        }

        $diskConfig = config('media-library.disk_name', 'public');
        $disk = is_string($diskConfig) ? $diskConfig : 'public';
        $safeFileName = $this->safeFileName($fileName, $hex, $extension);

        $mimeType = is_string($descriptor['mime_type'] ?? null) ? $descriptor['mime_type'] : 'application/octet-stream';
        $collectionName = is_string($descriptor['collection_name'] ?? null) ? $descriptor['collection_name'] : 'default';

        $media = new Media;
        $media->forceFill([
            'model_type' => $temporaryOwner->getMorphClass(),
            'model_id' => $temporaryOwner->getKey(),
            'collection_name' => $collectionName,
            'name' => pathinfo($safeFileName, PATHINFO_FILENAME),
            'file_name' => $safeFileName,
            'mime_type' => $mimeType,
            'disk' => $disk,
            'conversions_disk' => $disk,
            'size' => $size,
            'manipulations' => [],
            'custom_properties' => ['checksum' => $checksum],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ])->save();

        try {
            rewind($mediaStream);
            Storage::disk($disk)->put($media->getPathRelativeToRoot(), $mediaStream);
        } finally {
            if (is_resource($mediaStream)) {
                fclose($mediaStream);
            }
        }

        return $media->getKey();
    }

    /**
     * @return array{0: resource, 1: int}
     */
    private function verifiedMediaStream(ZipArchive $archive, string $entryPath, string $checksum): array
    {
        $stat = $archive->statName($entryPath);
        if ($stat === false) {
            throw new RuntimeException(sprintf('Media binary [%s] is missing from archive.', $entryPath));
        }

        $maxBytes = $this->configuredByteLimit('migration-assistant.limits.max_media_bytes', 50 * 1024 * 1024);
        $declaredSize = $this->entrySize($stat);
        if ($maxBytes > 0 && $declaredSize > $maxBytes) {
            throw new RuntimeException(sprintf('Media binary [%s] exceeds the maximum import size.', $entryPath));
        }

        $source = $archive->getStream($entryPath);
        if ($source === false) {
            throw new RuntimeException(sprintf('Media binary [%s] is missing from archive.', $entryPath));
        }

        $context = hash_init('sha256');
        $size = 0;
        $destination = fopen('php://temp', 'w+b');
        if ($destination === false) {
            fclose($source);
            throw new RuntimeException(sprintf('Failed to buffer media binary [%s].', $entryPath));
        }

        try {
            while (! feof($source)) {
                $chunk = fread($source, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException(sprintf('Failed to read media binary [%s].', $entryPath));
                }

                $size += strlen($chunk);
                if ($maxBytes > 0 && $size > $maxBytes) {
                    throw new RuntimeException(sprintf('Media binary [%s] exceeds the maximum import size.', $entryPath));
                }

                hash_update($context, $chunk);

                if (fwrite($destination, $chunk) === false) {
                    throw new RuntimeException(sprintf('Failed to buffer media binary [%s].', $entryPath));
                }
            }
        } catch (RuntimeException $runtimeException) {
            fclose($destination);

            throw $runtimeException;
        } finally {
            fclose($source);
        }

        $actualChecksum = 'sha256-' . hash_final($context);
        if (! hash_equals($checksum, $actualChecksum)) {
            fclose($destination);
            throw new RuntimeException(sprintf('Media binary [%s] checksum mismatch.', $entryPath));
        }

        return [$destination, $size];
    }

    /**
     * @param  array<string, mixed>  $stat
     */
    private function entrySize(array $stat): int
    {
        $size = $stat['size'] ?? 0;

        return is_numeric($size) ? (int) $size : 0;
    }

    private function configuredByteLimit(string $key, int $default): int
    {
        $maxBytesConfig = config($key, $default);

        return is_numeric($maxBytesConfig) ? $maxBytesConfig : $default;
    }

    private function safeFileName(string $fileName, string $hex, string $extension): string
    {
        $normalized = str_replace('\\', '/', str_replace("\0", '', $fileName));
        $basename = basename($normalized);

        if (in_array($basename, ['', '.', '..'], true)) {
            return $hex . ($extension === '' ? '' : '.' . $extension);
        }

        return $basename;
    }
}
