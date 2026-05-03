<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildLiveAnalyticsStatsAction
{
    use AsAction;

    /**
     * @return Collection<int, array<string, int|string>>
     */
    public function handle(int $minutes = 15, ?int $siteId = null, ?int $limit = 5): Collection
    {
        $startsAt = now()->subMinutes($minutes)->toImmutable();
        $endsAt = now()->toImmutable();
        $topPages = $this->topPages($startsAt, $endsAt, $siteId, $limit);

        return collect([
            [
                'id' => 'live-page-views',
                'metric' => __('capell-analytics::widgets.live_page_views'),
                'value' => $this->pageViews($startsAt, $endsAt, $siteId),
            ],
            [
                'id' => 'live-active-visits',
                'metric' => __('capell-analytics::widgets.live_active_visits'),
                'value' => $this->activeVisits($startsAt, $endsAt, $siteId),
            ],
            [
                'id' => 'live-top-page',
                'metric' => __('capell-analytics::widgets.live_top_page'),
                'value' => $topPages->first()['path'] ?? '-',
            ],
        ]);
    }

    /**
     * @return Collection<int, array{path: string, page_views: int}>
     */
    public function topPages(CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?int $siteId = null, ?int $limit = 5): Collection
    {
        return AnalyticsEvent::query()
            ->select([
                'path',
                DB::raw('COUNT(*) as page_views'),
            ])
            ->where('type', AnalyticsEventType::PageView)
            ->whereBetween('occurred_at', [$startsAt, $endsAt])
            ->when($siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $siteId))
            ->groupBy('path')
            ->orderByDesc('page_views')
            ->orderBy('path')
            ->limit($limit ?? 5)
            ->get()
            ->map(fn (AnalyticsEvent $event): array => [
                'path' => (string) $event->path,
                'page_views' => (int) $event->page_views,
            ]);
    }

    private function pageViews(CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?int $siteId): int
    {
        return AnalyticsEvent::query()
            ->where('type', AnalyticsEventType::PageView)
            ->whereBetween('occurred_at', [$startsAt, $endsAt])
            ->when($siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $siteId))
            ->count();
    }

    private function activeVisits(CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?int $siteId): int
    {
        return AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$startsAt, $endsAt])
            ->when($siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $siteId))
            ->whereNotNull('visit_id')
            ->distinct('visit_id')
            ->count('visit_id');
    }
}
