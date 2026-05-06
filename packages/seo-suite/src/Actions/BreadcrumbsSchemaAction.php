<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\SeoSuite\Enums\SchemaEntityTypeEnum;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array run(Page $page, Site $site, Language $language)
 */
class BreadcrumbsSchemaAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): array
    {
        $page->loadMissing('translations.language');

        $canonicalPages = PageLoader::getCanonicalPages($page, $language);
        $ancestors = PageLoader::getPageAncestors($page, $language, $site);

        if ($canonicalPages->isNotEmpty()) {
            $return = [];

            $canonicalPages->each(function (Page $canonicalPage) use ($language, $site, &$return): void {
                $item = [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    '@id' => $canonicalPage->pageUrl?->full_url !== null && $canonicalPage->pageUrl?->full_url !== ''
                        ? SchemaEntityTypeEnum::BreadcrumbList->toId($canonicalPage->pageUrl->full_url)
                        : null,
                    'itemListElement' => [],
                ];

                $canonicalPageAncestors = PageLoader::getPageAncestors($canonicalPage, $language, $site);

                $canonicalPageAncestors?->each(function (Page $ancestorPage, int $index) use (&$item): void {
                    $item['itemListElement'][] = [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => strip_tags((string) $ancestorPage->translation->label),
                        'item' => $ancestorPage->pageUrl->full_url,
                    ];
                });

                $return[] = $item;
            });

            return $return;
        }

        if ($ancestors?->isNotEmpty()) {
            $item = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                '@id' => $page->pageUrl?->full_url !== null && $page->pageUrl?->full_url !== ''
                    ? SchemaEntityTypeEnum::BreadcrumbList->toId($page->pageUrl->full_url)
                    : null,
                'itemListElement' => [],
            ];

            $ancestors->each(function (Page $ancestorPage, int $index) use (&$item): void {
                $item['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => strip_tags((string) $ancestorPage->translation->label),
                    'item' => $ancestorPage->pageUrl->full_url,
                ];
            });

            return [$item];
        }

        return [];
    }
}
