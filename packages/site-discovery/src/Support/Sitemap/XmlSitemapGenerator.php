<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Sitemap;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SiteDiscovery\Data\SitemapPageData;
use Capell\SiteDiscovery\Data\SitemapUrlItemData;
use Capell\SiteDiscovery\Exceptions\SitemapGeneratorException;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class XmlSitemapGenerator
{
    /**
     * Optional factory for injecting a testable SitemapGenerator instance.
     */
    protected ?Closure $generatorFactory = null;

    /**
     * Set a custom generator factory (for tests).
     */
    public function setGeneratorFactory(?Closure $factory): void
    {
        $this->generatorFactory = $factory;
    }

    /**
     * Delete all sitemap files (main, chunks, state) for every domain of the site.
     */
    public function delete(Site $site): void
    {
        $disk = config('capell.sitemap.disk', 'local');
        $directory = config('capell.sitemap.directory', 'sitemaps');
        $storage = Storage::disk($disk);
        $state = new SitemapStateStore($disk, $directory);

        $this->ensureDirectoryExists($storage, $directory);

        $site->siteDomains->each(function (SiteDomain $domain) use ($directory, $storage, $state): void {
            $domainKey = $domain->getDomainKey();

            // Main sitemap file
            $storage->delete($directory . '/' . $domainKey . '.xml');

            // Chunk files: {domainKey}-p{n}.xml
            foreach ($storage->files($directory) as $file) {
                $basename = basename($file);
                if (str_starts_with($basename, $domainKey . '-p') && str_ends_with($basename, '.xml')) {
                    $storage->delete($file);
                }
            }

            // State file
            $state->delete($domainKey);
        });
    }

    /**
     * Backwards-compatible API to generate the sitemap without progress callbacks.
     */
    public function generate(Site $site): string
    {
        $site->loadMissing('siteDomains.language');

        $domain = $site->siteDomains->first();
        if ($domain === null) {
            throw new SitemapGeneratorException('No site domain found for site ID ' . $site->id);
        }

        $this->process($site);

        $disk = config('capell.sitemap.disk', 'local');
        $directory = config('capell.sitemap.directory', 'sitemaps');
        $filename = $domain->getDomainKey() . '.xml';
        $filePath = $directory . '/' . $filename;
        $storage = Storage::disk($disk);

        if (! $storage->exists($filePath)) {
            throw new SitemapGeneratorException(
                '[SitemapGenerator] Sitemap XML file not found: ' . $filePath .
                ' | path_exists=' . ($storage->exists($filePath) ? 'yes' : 'no') .
                ' | dir_exists=' . ($storage->exists($directory) ? 'yes' : 'no') .
                ' | dir_contents=' . json_encode($storage->exists($directory) ? $storage->allFiles($directory) : null),
            );
        }

        $xml = $storage->get($filePath);
        throw_if($xml === false, SitemapGeneratorException::class, 'Failed to read sitemap XML file: ' . $filePath);

        return $xml;
    }

    /**
     * Generate sitemaps for the given site with optional progress callbacks.
     *
     * The $end closure receives: (int $total, string $filePath)
     */
    public function process(
        Site $site,
        ?Closure $start = null,
        ?Closure $prepare = null,
        ?Closure $checkpoint = null,
        ?Closure $end = null,
    ): void {
        $site->loadMissing('siteDomains.language');

        $site->siteDomains->each(function (SiteDomain $domain) use ($site, $start, $prepare, $checkpoint, $end): void {
            $this->generateForDomain($site, $domain, $start, $prepare, $checkpoint, $end);
        });
    }

    /**
     * Incremental variant: only rewrites a domain's sitemap when page state has changed
     * since the last run. Also handles sitemap index generation for large sitemaps.
     *
     * The $end closure receives: (int $total, string $filePath, bool $regenerated)
     * $regenerated is true when the XML was rewritten, false when it was skipped.
     */
    public function processIncremental(
        Site $site,
        ?Closure $start = null,
        ?Closure $prepare = null,
        ?Closure $checkpoint = null,
        ?Closure $end = null,
    ): void {
        $site->loadMissing('siteDomains.language');

        $site->siteDomains->each(function (SiteDomain $domain) use ($site, $start, $prepare, $checkpoint, $end): void {
            $this->generateForDomainIncremental($site, $domain, $start, $prepare, $checkpoint, $end);
        });
    }

    protected function generateForDomain(
        Site $site,
        SiteDomain $domain,
        ?Closure $start = null,
        ?Closure $prepare = null,
        ?Closure $checkpoint = null,
        ?Closure $end = null,
    ): void {
        $disk = config('capell.sitemap.disk', 'local');
        $directory = config('capell.sitemap.directory', 'sitemaps');
        $storage = Storage::disk($disk);

        $this->ensureDirectoryExists($storage, $directory);

        if ($this->generatorFactory instanceof Closure) {
            ($this->generatorFactory)($site, $domain, $start, $prepare, $checkpoint, $end);

            return;
        }

        if ($start instanceof Closure) {
            $start($domain);
        }

        Cache::forget(CacheEnum::sitemapPages($site->id, $domain->language->id));

        $builder = new SitemapBuilder($site, $domain, $domain->language);
        $pages = $builder->build();

        $items = $this->flattenPages($pages->all());
        $total = count($items);

        if ($prepare instanceof Closure) {
            $prepare($total, $domain->getDomainKey());
        }

        if ($total > 0) {
            foreach ($items as $item) {
                if ($checkpoint instanceof Closure) {
                    $checkpoint($item->loc);
                }
            }

            $filePath = $this->writeItems($storage, $directory, $domain, $items);

            // Save state so the next incremental run has a baseline.
            $state = new SitemapStateStore($disk, $directory);
            $state->save($domain->getDomainKey(), $state->buildUrlMap($items));
        } else {
            $filePath = $directory . '/' . $domain->getDomainKey() . '.xml';
        }

        if ($end instanceof Closure) {
            $end($total, $filePath);
        }
    }

    protected function generateForDomainIncremental(
        Site $site,
        SiteDomain $domain,
        ?Closure $start = null,
        ?Closure $prepare = null,
        ?Closure $checkpoint = null,
        ?Closure $end = null,
    ): void {
        $disk = config('capell.sitemap.disk', 'local');
        $directory = config('capell.sitemap.directory', 'sitemaps');
        $storage = Storage::disk($disk);
        $domainKey = $domain->getDomainKey();

        $this->ensureDirectoryExists($storage, $directory);

        if ($this->generatorFactory instanceof Closure) {
            ($this->generatorFactory)($site, $domain, $start, $prepare, $checkpoint, $end);

            return;
        }

        if ($start instanceof Closure) {
            $start($domain);
        }

        Cache::forget(CacheEnum::sitemapPages($site->id, $domain->language->id));

        $builder = new SitemapBuilder($site, $domain, $domain->language);
        $pages = $builder->build();
        $items = $this->flattenPages($pages->all());
        $total = count($items);

        if ($prepare instanceof Closure) {
            $prepare($total, $domain->getDomainKey());
        }

        $state = new SitemapStateStore($disk, $directory);
        $currentMap = $state->buildUrlMap($items);
        $storedMap = $state->load($domainKey);

        if (! $state->hasChanged($currentMap, $storedMap)) {
            // Nothing changed — skip disk I/O entirely.
            if ($end instanceof Closure) {
                $end($total, $directory . '/' . $domainKey . '.xml', false);
            }

            return;
        }

        if ($total > 0) {
            foreach ($items as $item) {
                if ($checkpoint instanceof Closure) {
                    $checkpoint($item->loc);
                }
            }

            $filePath = $this->writeItems($storage, $directory, $domain, $items);
            $state->save($domainKey, $currentMap);
        } else {
            $filePath = $directory . '/' . $domainKey . '.xml';
        }

        if ($end instanceof Closure) {
            $end($total, $filePath, true);
        }
    }

    /**
     * Write items to disk, splitting into chunks + an index file when the
     * item count exceeds capell.sitemap.max_urls_per_file (default 50 000).
     *
     * Returns the path of the primary file that was written (main or index).
     *
     * @param  array<int, SitemapUrlItemData>  $items
     */
    protected function writeItems(
        Filesystem $storage,
        string $directory,
        SiteDomain $domain,
        array $items,
    ): string {
        $maxPerFile = max(1, config('capell.sitemap.max_urls_per_file', 50000));
        $domainKey = $domain->getDomainKey();
        $mainPath = $directory . '/' . $domainKey . '.xml';

        if (count($items) <= $maxPerFile) {
            $storage->put($mainPath, $this->toXml($items));

            return $mainPath;
        }

        // --- paginated: write chunks then an index ---
        $chunks = array_chunk($items, $maxPerFile);
        /** @phpstan-ignore-next-line cast.useless */
        $xmlPath = rtrim((string) config('capell.sitemap.xml_path', '/sitemap-xml'), '/');
        $baseUrl = rtrim($domain->full_url, '/') . $xmlPath;
        $now = now()->format(DATE_ATOM);
        $indexEntries = [];

        foreach ($chunks as $n => $chunk) {
            $chunkNum = $n + 1;
            $chunkFile = $directory . '/' . $domainKey . '-p' . $chunkNum . '.xml';
            $storage->put($chunkFile, $this->toXml($chunk));
            $indexEntries[] = [
                'loc' => $baseUrl . '?p=' . $chunkNum,
                'lastmod' => $now,
            ];
        }

        $storage->put($mainPath, $this->toIndexXml($indexEntries));

        return $mainPath;
    }

    protected function ensureDirectoryExists(Filesystem $storage, string $directory): void
    {
        if (! $storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }
    }

    /**
     * @param  array<int, SitemapPageData>  $sitemapPages
     * @return array<int, SitemapUrlItemData>
     */
    private function flattenPages(array $sitemapPages): array
    {
        $flat = [];

        foreach ($sitemapPages as $sitemapPage) {
            $this->appendPageAndChildren($flat, $sitemapPage);
        }

        return $flat;
    }

    /**
     * @param  array<int, SitemapUrlItemData>  $flat
     */
    private function appendPageAndChildren(array &$flat, SitemapPageData $sitemapPage): void
    {
        if ($sitemapPage->url === '') {
            return;
        }

        $flat[] = new SitemapUrlItemData(
            loc: $sitemapPage->url,
            lastmod: $sitemapPage->lastModified,
            changefreq: $sitemapPage->changeFrequency,
            priority: $sitemapPage->priority !== null ? number_format($sitemapPage->priority, 1, '.', '') : null,
        );

        foreach ($this->normalizeChildren($sitemapPage->children) as $child) {
            $this->appendPageAndChildren($flat, $child);
        }
    }

    /**
     * @param  Collection<int, SitemapPageData>|null  $children
     * @return array<int, SitemapPageData>
     */
    private function normalizeChildren(?Collection $children): array
    {
        if (! $children instanceof Collection) {
            return [];
        }

        return $children
            ->filter(fn (mixed $child): bool => $child instanceof SitemapPageData)
            ->values()
            ->all();
    }

    /**
     * Build a standard <urlset> XML document.
     *
     * @param  array<int, SitemapUrlItemData>  $items
     */
    private function toXml(array $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($items as $item) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($item->loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
            $lastModified = $item->lastmod;
            if ($lastModified !== null && $lastModified !== '') {
                if ($lastModified instanceof DateTimeInterface) {
                    $lastModified = $lastModified->format(DATE_ATOM);
                }

                $xml .= '<lastmod>' . htmlspecialchars($lastModified, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</lastmod>';
            }

            if ($item->changefreq !== null && $item->changefreq !== '') {
                /** @phpstan-ignore-next-line cast.useless */
                $xml .= '<changefreq>' . htmlspecialchars((string) $item->changefreq, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</changefreq>';
            }

            if ($item->priority !== null && $item->priority !== '') {
                /** @phpstan-ignore-next-line cast.useless */
                $xml .= '<priority>' . htmlspecialchars((string) $item->priority, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</priority>';
            }

            $xml .= '</url>';
        }

        return $xml . '</urlset>';
    }

    /**
     * Build a <sitemapindex> XML document referencing chunk files.
     *
     * @param  array<int, array{loc: string, lastmod: string}>  $sitemaps
     */
    private function toIndexXml(array $sitemaps): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($sitemaps as $sitemap) {
            $xml .= '<sitemap>';
            $xml .= '<loc>' . htmlspecialchars($sitemap['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
            $xml .= '<lastmod>' . htmlspecialchars($sitemap['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</lastmod>';
            $xml .= '</sitemap>';
        }

        return $xml . '</sitemapindex>';
    }
}
