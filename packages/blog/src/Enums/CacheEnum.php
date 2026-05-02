<?php

declare(strict_types=1);

namespace Capell\Blog\Enums;

enum CacheEnum: string
{
    /**
     * Generate a cache key for blog page.
     *
     * @param  int  $siteId  The site ID
     * @param  int|string|null  $languageId  The language ID
     * @param  string  $type  The type
     * @return string The cache key
     */
    public static function blogPage(int $siteId, int|string|null $languageId, string $type): string
    {
        return sprintf('site-%d-%s-%s-page', $siteId, $languageId ?? 'null', strtolower($type));
    }

    /**
     * Generate a cache key for archive page.
     *
     * @param  int  $siteId  The site ID
     * @param  int  $languageId  The language ID
     * @return string The cache key
     */
    public static function archivePage(int $siteId, int $languageId): string
    {
        return sprintf('site-%d-%d-archive-page', $siteId, $languageId);
    }

    /**
     * Generate a cache key for archives.
     *
     * @param  int  $siteId  The site ID
     * @param  int  $languageId  The language ID
     * @param  string  $group  The group
     * @param  int|null  $limit  The limit (optional)
     * @param  int|null  $paginationPage  The pagination page (optional)
     * @return string The cache key
     */
    public static function archives(int $siteId, int $languageId, string $group, ?int $limit, ?int $paginationPage): string
    {
        return sprintf('site-%d-%d-%s-%s-page-%s', $siteId, $languageId, $group, $limit, $paginationPage);
    }

    /**
     * Generate a cache key for page tags.
     *
     * @param  int  $pageId  The page ID
     * @return string The cache key
     */
    public static function pageTags(int $pageId): string
    {
        return 'page-tags-' . $pageId;
    }

    /**
     * Generate a cache key for tag results page.
     *
     * @param  int  $siteId  The site ID
     * @param  int  $languageId  The language ID
     * @return string The cache key
     */
    public static function tagResultsPage(int $siteId, int $languageId): string
    {
        return sprintf('site-%d-%d-tag-page', $siteId, $languageId);
    }

    /**
     * Generate a cache key for site tags.
     *
     * @param  int  $siteId  The site ID
     * @param  int  $languageId  The language ID
     * @param  int|null  $limit  The limit (optional)
     * @param  int|null  $paginationPage  The pagination page (optional)
     * @return string The cache key
     */
    public static function siteTags(int $siteId, int $languageId, bool $hasArticles, ?int $limit = null, ?int $paginationPage = null): string
    {
        $cacheKey = sprintf('site-tags-%d-lang-%d-limit-%s', $siteId, $languageId, $limit);

        $cacheKey .= $hasArticles ? '-with-articles' : '-all';

        if ($paginationPage !== null) {
            $cacheKey .= '-page-' . $paginationPage;
        }

        return $cacheKey;
    }

    /**
     * Generate a cache key for tag page.
     *
     * @param  int  $siteId  The site ID
     * @param  int  $languageId  The language ID
     * @param  string  $slug  The slug
     * @return string The cache key
     */
    public static function tagPage(int $siteId, int $languageId, string $slug): string
    {
        return sprintf('site-%d-lang-%d-tag-%s-page', $siteId, $languageId, $slug);
    }
}
