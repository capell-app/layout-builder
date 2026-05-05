<?php

declare(strict_types=1);

namespace Capell\ExampleSites\Support\Creator;

use Exception;
use FilesystemIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ZipArchive;

final class DemoResourceResolver
{
    public function resolve(?string $folder): string
    {
        $folder = in_array($folder, [null, '', '0'], true) ? null : ltrim($folder, '/');

        $localBase = realpath(__DIR__ . '/../../../demo');
        if ($localBase !== false) {
            $path = realpath($localBase . ($folder === null ? '' : '/' . $folder));
            throw_if($path === false, Exception::class, 'Demo resource path not found: ' . ($folder ?? ''));

            return $path;
        }

        $storageBase = $this->ensureStorageDemoResources();
        $path = realpath($storageBase . ($folder === null ? '' : '/' . $folder));
        throw_if($path === false, Exception::class, 'Demo resource path not found: ' . ($folder ?? ''));

        return $path;
    }

    public function ensureStorageDemoResources(): string
    {
        $capellDir = storage_path('app/capell');
        $demoDir = $capellDir . '/demo';

        if (is_dir($demoDir) && (new FilesystemIterator($demoDir))->valid()) {
            return $demoDir;
        }

        File::ensureDirectoryExists($capellDir);

        $lockHandle = @fopen($capellDir . '/demo.lock', 'c');
        if ($lockHandle !== false) {
            @flock($lockHandle, LOCK_EX);
        }

        $tmpZip = null;
        $stagingDir = null;

        try {
            if (is_dir($demoDir) && (new FilesystemIterator($demoDir))->valid()) {
                return $demoDir;
            }

            $tmpZip = $capellDir . '/demo.' . Str::random(12) . '.zip';
            $stagingDir = $capellDir . '/demo_extract_' . Str::random(12);
            $maxArchiveBytes = $this->demoArchiveMaxBytes();

            File::deleteDirectory($stagingDir);
            File::ensureDirectoryExists($stagingDir);

            $archiveUrl = config('capell-example-sites.archive.url', 'https://capell.app/demo.zip');
            $response = Http::timeout(60)
                ->retry(2, 500)
                ->withOptions([
                    'sink' => $tmpZip,
                    'progress' => function (int $downloadTotal, int $downloadedBytes) use ($maxArchiveBytes): void {
                        throw_if($downloadTotal > $maxArchiveBytes || $downloadedBytes > $maxArchiveBytes, Exception::class, 'Demo zip archive exceeds the configured maximum size.');
                    },
                ])
                ->get(is_string($archiveUrl) && $archiveUrl !== '' ? $archiveUrl : 'https://capell.app/demo.zip');

            throw_if(! $response->ok(), Exception::class, 'Unable to download demo assets: ' . $response->status());

            $this->assertDemoArchiveResponseSize($response->header('Content-Length'), $maxArchiveBytes);

            if (! File::exists($tmpZip) || File::size($tmpZip) === 0) {
                $body = $response->body();

                throw_if(strlen($body) > $maxArchiveBytes, Exception::class, 'Demo zip archive exceeds the configured maximum size.');

                File::put($tmpZip, $body);
            }

            throw_if(File::size($tmpZip) > $maxArchiveBytes, Exception::class, 'Demo zip archive exceeds the configured maximum size.');

            $this->assertDemoZipChecksum($tmpZip);

            $zip = new ZipArchive;
            $opened = $zip->open($tmpZip);
            throw_if($opened !== true, Exception::class, 'Unable to open demo zip archive');

            $this->assertSafeDemoZipEntries($zip);

            $extracted = $zip->extractTo($stagingDir);
            $zip->close();

            throw_if($extracted !== true, Exception::class, 'Unable to extract demo zip archive');

            $extractedBase = is_dir($stagingDir . '/demo') ? $stagingDir . '/demo' : $stagingDir;

            File::deleteDirectory($demoDir);
            File::moveDirectory($extractedBase, $demoDir);

            File::delete($tmpZip);
            File::deleteDirectory($stagingDir);

            return $demoDir;
        } finally {
            if (is_string($tmpZip) && File::exists($tmpZip)) {
                File::delete($tmpZip);
            }

            if (is_string($stagingDir) && File::isDirectory($stagingDir)) {
                File::deleteDirectory($stagingDir);
            }

            if (is_resource($lockHandle)) {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
            }
        }
    }

    public function assertSafeDemoZipEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            throw_if(
                ! is_string($entryName) || $entryName === '' || $this->isUnsafeDemoZipEntry($entryName) || $this->isDemoZipSymlink($zip, $entryName),
                Exception::class,
                'Unsafe demo zip entry: ' . (is_string($entryName) ? $entryName : '[unknown]'),
            );
        }
    }

    private function demoArchiveMaxBytes(): int
    {
        $configured = config('capell-example-sites.archive.max_bytes', 52428800);
        $maxBytes = filter_var($configured, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($maxBytes) ? $maxBytes : 52428800;
    }

    private function assertDemoArchiveResponseSize(?string $contentLength, int $maxArchiveBytes): void
    {
        if ($contentLength === null || $contentLength === '' || ! ctype_digit($contentLength)) {
            return;
        }

        throw_if((int) $contentLength > $maxArchiveBytes, Exception::class, 'Demo zip archive exceeds the configured maximum size.');
    }

    private function assertDemoZipChecksum(string $zipPath): void
    {
        $expectedChecksum = config('capell-example-sites.archive.checksum');

        throw_if(
            ! is_string($expectedChecksum) || preg_match('/^[a-f0-9]{64}$/i', $expectedChecksum) !== 1,
            Exception::class,
            'Demo zip checksum is not configured.',
        );

        $actualChecksum = hash_file('sha256', $zipPath);

        throw_if(
            ! is_string($actualChecksum) || ! hash_equals(strtolower($expectedChecksum), strtolower($actualChecksum)),
            Exception::class,
            'Demo zip checksum mismatch.',
        );
    }

    private function isUnsafeDemoZipEntry(string $entryName): bool
    {
        if (str_contains($entryName, "\0") || str_contains($entryName, '\\')) {
            return true;
        }

        if (str_starts_with($entryName, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $entryName) === 1) {
            return true;
        }

        return in_array('..', explode('/', $entryName), true);
    }

    private function isDemoZipSymlink(ZipArchive $zip, string $entryName): bool
    {
        $hasAttributes = $zip->getExternalAttributesName($entryName, $operatingSystem, $externalAttributes);
        if ($hasAttributes === false || $operatingSystem !== ZipArchive::OPSYS_UNIX) {
            return false;
        }

        return (($externalAttributes >> 16) & 0170000) === 0120000;
    }
}
