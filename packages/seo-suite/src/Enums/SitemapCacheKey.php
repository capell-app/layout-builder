<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

enum SitemapCacheKey: string
{
    case Sitemaps = 'sitemaps';
    case Generating = 'sitemaps:generating';
}
