<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Sitemap;

use Capell\SeoSuite\Data\SitemapUrlItemData;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Persists the URL→lastmod state for each domain sitemap between runs,
 * enabling incremental regeneration to skip domains that have not changed.
 *
 * State files are stored at:
 *   {sitemap_directory}/.state/{domainKey}.json
 */
class SitemapStateStore
{
    public function __construct(
        private readonly string $disk,
        private readonly string $directory,
    ) {}

    /**
     * Load the stored URL→lastmod map for the given domain key.
     *
     * @return array<string, string>
     */
    public function load(string $domainKey): array
    {
        $storage = Storage::disk($this->disk);
        $path = $this->statePath($domainKey);

        if (! $storage->exists($path)) {
            return [];
        }

        $raw = $storage->get($path);
        if ($raw === null || $raw === false) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data['urls'] ?? null) ? $data['urls'] : [];
    }

    /**
     * Persist the URL→lastmod map for the given domain key.
     *
     * @param  array<string, string>  $urlLastModMap
     */
    public function save(string $domainKey, array $urlLastModMap): void
    {
        $storage = Storage::disk($this->disk);
        $stateDir = $this->directory . '/.state';

        if (! $storage->exists($stateDir)) {
            $storage->makeDirectory($stateDir);
        }

        $storage->put($this->statePath($domainKey), json_encode([
            'generated_at' => now()->toAtomString(),
            'urls' => $urlLastModMap,
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Remove the stored state for the given domain key.
     */
    public function delete(string $domainKey): void
    {
        $storage = Storage::disk($this->disk);
        $path = $this->statePath($domainKey);

        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    /**
     * Return true if $current differs from $stored in any way
     * (added, removed, or lastmod-changed URLs).
     *
     * @param  array<string, string>  $current
     * @param  array<string, string>  $stored
     */
    public function hasChanged(array $current, array $stored): bool
    {
        if (count($current) !== count($stored)) {
            return true;
        }

        foreach ($current as $url => $lastmod) {
            if (! isset($stored[$url]) || $stored[$url] !== $lastmod) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a URL→lastmod string map from a flat list of sitemap URL items.
     *
     * @param  array<int, SitemapUrlItemData>  $items
     * @return array<string, string>
     */
    public function buildUrlMap(array $items): array
    {
        $map = [];

        foreach ($items as $item) {
            $lastmod = $item->lastmod;

            if ($lastmod instanceof DateTimeInterface) {
                $lastmod = $lastmod->format(DATE_ATOM);
            }

            $map[$item->loc] = $lastmod ?? '';
        }

        return $map;
    }

    private function statePath(string $domainKey): string
    {
        return $this->directory . '/.state/' . $domainKey . '.json';
    }
}
