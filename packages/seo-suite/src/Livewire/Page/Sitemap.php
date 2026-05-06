<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Livewire\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Site;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\SeoSuite\Support\Sitemap\SitemapBuilder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Sitemap extends AbstractPage
{
    protected static string $defaultView = 'capell::livewire.page.sitemap';

    protected function setup(): void
    {
        $page = Frontend::page();
        $url = $page->pageUrl->full_url;
        $site = Frontend::site();

        /** @noRector RectorLaravel\Rector\If_\AbortIfRector */
        $requestUri = request()->getRequestUri();
        if ($page instanceof Pageable && (str_ends_with($requestUri, '-xml') || str_ends_with($url, '-xml'))) {
            $downloadFilename = (str_ends_with($url, '-xml') ? substr($url, 0, -4) : $url) . '.xml';
            throw new HttpResponseException($this->returnXmlSitemap($downloadFilename, $site));
        }

        $sitemapLoader = new SitemapBuilder(
            site: $site,
            domain: $site->siteDomain,
            language: Frontend::language(),
        );

        $this->results = $sitemapLoader->build();
    }

    private function returnXmlSitemap(string $downloadFilename, Site $site): Response|StreamedResponse
    {
        $domainKey = $site->siteDomain->getDomainKey();

        // Support paginated sitemaps: ?p=N serves the Nth chunk file.
        $chunkPage = request()->query('p');
        if ($chunkPage !== null && ctype_digit((string) $chunkPage) && (int) $chunkPage > 0) {
            $filename = $domainKey . '-p' . (int) $chunkPage . '.xml';
        } else {
            $filename = $domainKey . '.xml';
        }

        $storage = Storage::disk(config('capell.sitemap.disk'));
        $directory = config('capell.sitemap.directory');
        $filePath = $directory . '/' . $filename;

        abort_unless($storage->exists($filePath), 404);

        $size = $storage->size($filePath);
        $lastModifiedTs = $storage->lastModified($filePath);
        $lastModified = gmdate('D, d M Y H:i:s \G\M\T', is_numeric($lastModifiedTs) ? $lastModifiedTs : Date::now()->getTimestamp());

        // Use SHA-256 for ETag digest (stronger than sha1)
        $fileContents = $storage->get($filePath);
        throw_if($fileContents === false, RuntimeException::class, 'Unable to read file contents.');

        $etagDigest = hash('sha256', (string) $fileContents);
        $weakEtag = 'W/"' . $etagDigest . '"';
        $strongEtag = '"' . $etagDigest . '"';
        $etag = $weakEtag;

        $ifNoneMatch = request()->header('If-None-Match');
        // Normalize header to array of trimmed tokens
        $rawEtags = is_array($ifNoneMatch) ? $ifNoneMatch : explode(',', (string) $ifNoneMatch);
        $clientEtags = array_values(array_filter(array_map(trim(...), $rawEtags), static fn (string $value): bool => $value !== ''));

        $matches = $ifNoneMatch !== null && (
            in_array($weakEtag, $clientEtags, true)
            || in_array($strongEtag, $clientEtags, true)
            || in_array($etagDigest, $clientEtags, true)
            || in_array('*', $clientEtags, true)
        );

        if ($matches) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=86400',
                'Expires' => now()->addDay()->toRfc7231String(),
                'Last-Modified' => $lastModified,
            ]);
        }

        $headers = [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
            'Expires' => now()->addDay()->toRfc7231String(),
            'Last-Modified' => $lastModified,
            'ETag' => $etag,
            'Content-Disposition' => 'attachment; filename="' . $downloadFilename . '"',
        ];

        // Use streamed response for large files (>1MB)
        if ($size > 1024 * 1024) {
            return response()->stream(function () use ($storage, $filePath): void {
                echo $storage->get($filePath);
            }, 200, $headers);
        }

        $contents = $storage->get($filePath);

        return response($contents, 200, $headers);
    }
}
