<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Capell\Core\Support\Sitemap\SitemapChainBuilder;
use Illuminate\Support\Collection;

class ArchivesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(CoreModelEnum::Page);

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
            url: $archivePage->pageUrl->full_url . sprintf('/%d-%d', $monthData->year, $monthData->month),
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
