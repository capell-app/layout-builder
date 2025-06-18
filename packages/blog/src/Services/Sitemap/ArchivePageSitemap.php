<?php

declare(strict_types=1);

namespace Capell\Blog\Services\Sitemap;

use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Core\Actions\EditPageUrlAction;
use Capell\Core\Data\ArchiveMonthData;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Services\Sitemap\AbstractSitemapPages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ArchivePageSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        $cacheKey = sprintf('sitemap.archive_pages.%d.%d', $this->site->id, $this->language->id);

        return Cache::remember($cacheKey, 3600, function (): Collection {
            $archivePage = $this->getArchivePage($this->site, $this->language);
            if (! $archivePage instanceof Page) {
                return collect([]);
            }

            $archivesPage = $archivePage->parent;

            return collect([
                SitemapPageData::from([
                    'label' => $archivesPage->translation->title,
                    'url' => $archivePage->pageUrl->full_url,
                    'children' => $this->getArchivePages($archivePage),
                    'editUrl' => $this->withEditUrl ? EditPageUrlAction::run($archivePage) : null,
                ])
                    ->toArray(),
            ]);
        });
    }

    public function format(ArchiveMonthData $monthData, Page $archivePage): SitemapPageData
    {
        return SitemapPageData::from([
            'label' => $monthData->getDate()->format('F Y').' ('.$monthData->total.')',
            'url' => $archivePage->pageUrl->full_url.sprintf('/%d-%d', $monthData->year, $monthData->month),
            'editUrl' => $this->withEditUrl ? EditPageUrlAction::run($archivePage) : null,
        ]);
    }

    private function getArchivePage(Site $site, Language $language): ?Page
    {
        return once(function () use ($site, $language): ?Page {
            /** @var class-string<Page> $model */
            $model = CapellCore::getModel(ModelEnum::Page);

            return $model::getPageByType('archive', $site, $language);
        });
    }

    private function getArchivePages(Page $archivePage): Collection
    {
        return BlogLoader::getArchives(
            site: $this->site,
            language: $this->language,
            type: 'article',
            limit: config('capell-blog.sitemap.archives_limit', 100),
        )->map(fn (ArchiveMonthData $monthData): array => $this->format($monthData, $archivePage)->toArray());
    }
}
