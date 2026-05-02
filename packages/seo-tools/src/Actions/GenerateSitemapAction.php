<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models;
use Capell\Core\Models\Site;
use Capell\SeoTools\Enums\SitemapCacheKey;
use Capell\SeoTools\Support\Sitemap\XmlSitemapGenerator;
use Exception;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static string run(Models\Site $site)
 */
class GenerateSitemapAction
{
    use AsJob;
    use AsObject;

    protected string $cacheKey = SitemapCacheKey::Generating->value;

    public function handle(Site $site): string
    {
        try {
            $xml = resolve(XmlSitemapGenerator::class)->generate($site);

            $this->updateCache();

            Cache::forget(SitemapCacheKey::Sitemaps->value);

            return $xml;
        } catch (Throwable) {
            $this->updateCache();

            throw new Exception('Failed to generate sitemap');
        }
    }

    private function updateCache(): void
    {
        $remaining = Cache::get($this->cacheKey, 0) - 1;

        if ($remaining <= 0) {
            Cache::forget($this->cacheKey);
        } else {
            Cache::decrement($this->cacheKey);
        }
    }
}
