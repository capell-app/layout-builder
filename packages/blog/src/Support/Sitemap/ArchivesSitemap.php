<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
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

        $archivePage = $model::getFirstPageByTypeForSite('archive', $this->site, $this->language);
        if (! $archivePage instanceof Page) {
            return collect([]);
        }

        $monthChildren = $this->getArchiveMonths($archivePage);

        $parent = $archivePage->parent;
        if ($parent === null) {
            return collect($monthChildren);
        }

        $node = SitemapChainBuilder::build($parent, $monthChildren);

        return collect([$node]);
    }

    public function format(ArchiveMonthData $monthData, Page $archivePage): SitemapPageData
    {
        return SitemapPageData::from([
            'label' => $monthData->getDate()->format('F Y') . ' (' . $monthData->total . ')',
            'url' => $archivePage->pageUrl->full_url . sprintf('/%d-%d', $monthData->year, $monthData->month),
        ]);
    }

    private function getArchiveMonths(Page $archivePage): array
    {
        $archives = BlogLoader::getArchives(
            $this->site,
            $this->language,
            BlogTypeGroupEnum::Article->value,
        );

        return $archives->map(
            fn (ArchiveMonthData $archive): array => $this->format($archive, $archivePage)->toArray(),
        )->values()->all();
    }
}
