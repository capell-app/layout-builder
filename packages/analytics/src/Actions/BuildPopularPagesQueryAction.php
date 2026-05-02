<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsWindowData;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildPopularPagesQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, array{path: string, url: string, page_views: int, unique_visits: int, clicks: int}>
     */
    public function handle(AnalyticsWindowData $window, ?int $limit = null): Collection
    {
        $clicksByPath = $this->clicksByPath($window);

        $query = AnalyticsEvent::query()
            ->select([
                'path',
                DB::raw('MIN(url) as url'),
                DB::raw('COUNT(*) as page_views'),
                DB::raw('COUNT(DISTINCT visit_id) as unique_visits'),
            ])
            ->where('type', AnalyticsEventType::PageView)
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->groupBy('path')
            ->orderByDesc('page_views')
            ->orderBy('path');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (AnalyticsEvent $event): array => [
                'path' => (string) $event->path,
                'url' => (string) $event->url,
                'page_views' => $event->page_views,
                'unique_visits' => (int) $event->unique_visits,
                'clicks' => $clicksByPath[$event->path] ?? 0,
            ])
            ->values();
    }

    /**
     * @return array<string, int>
     */
    private function clicksByPath(AnalyticsWindowData $window): array
    {
        return AnalyticsEvent::query()
            ->select([
                'path',
                DB::raw('COUNT(*) as clicks'),
            ])
            ->where('type', AnalyticsEventType::Click)
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->groupBy('path')
            ->pluck('clicks', 'path')
            ->mapWithKeys(fn (mixed $clicks, string $path): array => [$path => (int) $clicks])
            ->all();
    }
}
