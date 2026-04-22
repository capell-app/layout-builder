<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Blog\Data\Dashboard\ArticleHealthData;
use Capell\Blog\Data\Dashboard\LanguageCoverageData;
use Capell\Blog\Data\Dashboard\TagCountData;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ArticleHealthWidget extends CapellWidget
{
    protected static string $settingsKey = 'article_health';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['developer', 'admin', 'super_admin'];

    protected string $view = 'capell-blog::filament.widgets.article-health';

    private static ?string $heading = 'Article health';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }

    private function getData(): ArticleHealthData
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = CapellCore::getModel(ModelEnum::Article);

        // Total counts
        $totalArticles = $articleModel::query()->count();
        $totalTags = Tag::query()->whereType('article')->count();
        $totalCategories = 0; // Blog uses tags, not separate categories

        // Status counts
        $publishedCount = $articleModel::query()
            ->whereNotNull('visible_from')
            ->where('visible_from', '<=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('visible_until')
                    ->orWhere('visible_until', '>', now());
            })
            ->count();

        $draftCount = $articleModel::query()
            ->whereNull('visible_from')
            ->count();

        $scheduledFutureCount = $articleModel::query()
            ->whereNotNull('visible_from')
            ->where('visible_from', '>', now())
            ->count();

        $expiredCount = $articleModel::query()
            ->whereNotNull('visible_until')
            ->where('visible_until', '<=', now())
            ->count();

        // Recent activity
        $sevenDaysAgo = now()->subDays(7);
        $recentlyCreatedCount = $articleModel::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();

        $recentlyUpdatedCount = $articleModel::query()
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();

        // Top tags
        $topTags = Tag::query()
            ->whereType('article')
            ->withCount('taggables')
            ->orderByDesc('taggables_count')
            ->limit(5)
            ->get()
            ->map(fn (Tag $tag): TagCountData => new TagCountData(
                name: $tag->name,
                articleCount: $tag->taggables_count ?? 0,
            ));

        // Language coverage
        $languageCoverage = $this->getLanguageCoverage($articleModel);

        return new ArticleHealthData(
            totalArticles: $totalArticles,
            totalTags: $totalTags,
            totalCategories: $totalCategories,
            publishedCount: $publishedCount,
            draftCount: $draftCount,
            scheduledFutureCount: $scheduledFutureCount,
            expiredCount: $expiredCount,
            recentlyCreatedCount: $recentlyCreatedCount,
            recentlyUpdatedCount: $recentlyUpdatedCount,
            topTags: $topTags,
            languageCoverage: $languageCoverage,
        );
    }

    /**
     * @param  class-string<Article>  $articleModel
     * @return Collection<int, LanguageCoverageData>
     */
    private function getLanguageCoverage(string $articleModel): Collection
    {
        $languages = Language::all();
        $coverage = [];

        foreach ($languages as $language) {
            $total = $articleModel::query()->count();
            $withTranslation = $articleModel::query()
                ->whereHas('translations', function (Builder $query) use ($language): void {
                    $query->where('language_id', $language->id);
                })
                ->count();

            $coverage[] = new LanguageCoverageData(
                language: $language->name,
                withTranslation: $withTranslation,
                withoutTranslation: $total - $withTranslation,
                total: $total,
            );
        }

        return collect($coverage);
    }
}
