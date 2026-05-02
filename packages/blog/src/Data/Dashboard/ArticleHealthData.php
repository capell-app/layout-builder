<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class ArticleHealthData extends Data
{
    /**
     * @param  Collection<int, TagCountData>  $topTags
     * @param  Collection<int, LanguageCoverageData>  $languageCoverage
     */
    public function __construct(
        public readonly int $totalArticles,
        public readonly int $totalTags,
        public readonly int $totalCategories,
        public readonly int $publishedCount,
        public readonly int $draftCount,
        public readonly int $scheduledFutureCount,
        public readonly int $expiredCount,
        public readonly int $recentlyCreatedCount,
        public readonly int $recentlyUpdatedCount,
        public readonly Collection $topTags,
        public readonly Collection $languageCoverage,
    ) {}
}
