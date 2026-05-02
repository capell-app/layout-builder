<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\Loader;

use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoTools\Data\SiteMapData;
use Capell\SeoTools\Enums\SitemapCacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SitemapLoader
{
    /**
     * @return array<int, SiteMapData>
     */
    public function all(): array
    {
        $directory = config('capell.sitemap.directory');

        $storage = Storage::disk(config('capell.sitemap.disk'));

        $sitemaps = Cache::remember(
            SitemapCacheKey::Sitemaps->value,
            30,
            function () use ($directory, $storage): array {
                $sitemaps = [];

                $sites = Site::with('siteDomains')->get();

                foreach ($sites as $site) {
                    $sitemapPage = $site->getFirstPageByType('sitemap');

                    if ($sitemapPage === null) {
                        continue;
                    }

                    $site->siteDomains->each(function (SiteDomain $domain) use (&$sitemaps, $storage, $directory): void {
                        $filename = $domain->getDomainKey() . '.xml';

                        if (! $storage->exists($directory . '/' . $filename)) {
                            return;
                        }

                        // Parse xml site map and count urls
                        $xml = simplexml_load_string(
                            (string) $storage->get($directory . '/' . $filename),
                            'SimpleXMLElement',
                            LIBXML_NONET,
                        );

                        if ($xml === false) {
                            return;
                        }

                        $total = count($xml->url);

                        $sitemaps[] = new SiteMapData(
                            name: $domain->name,
                            url: $domain->full_url . '/sitemap-xml',
                            total: $total,
                        );
                    });
                }

                return $sitemaps;
            },
        );

        return $sitemaps;
    }
}
