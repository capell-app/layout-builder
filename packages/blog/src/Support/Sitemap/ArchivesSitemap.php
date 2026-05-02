<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Actions\GenerateArchiveUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\SeoTools\Data\SitemapPageData;
use Capell\SeoTools\Support\Sitemap\AbstractSitemapPages;
use Capell\SeoTools\Support\Sitemap\SitemapChainBuilder;
use Illuminate\Support\Collection;

class ArchivesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        /** @var class-string<Page> $model */
        $model = Page::class;

        $maybeArchivePage = $model::getFirstPageByTypeForSite('archive', $this->site, $this->language);
        if (! ($maybeArchivePage instanceof Pageable)) {
            return collect([]);
        }

        $archivePage = $maybeArchivePage;
        $monthChildren = $this->getArchiveMonths($archivePage);

        if ($archivePage->parent === null) {
            return $monthChildren;
        }

        $node = SitemapChainBuilder::build($archivePage->parent, $monthChildren, withEditUrl: $this->withEditUrl);

        return collect([$node]);
    }

    public function format(ArchiveMonthData $monthData, Page $archivePage): SitemapPageData
    {
        return new SitemapPageData(
            label: $monthData->getDate()->format('F Y') . ' (' . $monthData->total . ')',
            url: GenerateArchiveUrl::run($archivePage->pageUrl, $monthData),
        );
    }

    private function getArchiveMonths(Page $archivePage): Collection
    {
        $archives = BlogLoader::getArchives(
            $this->site,
            $this->language,
            BlogTypeGroupEnum::Article->value,
        );

        return $archives->map(
            fn (ArchiveMonthData $archive): SitemapPageData => $this->format($archive, $archivePage),
        )->values();
    }
}
