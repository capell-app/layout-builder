<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions\Dashboard;

use Capell\Admin\Data\Dashboard\SiteStatsData;
use Capell\Core\Models\AccessLog;
use Capell\Core\Models\Page;
use Capell\Workspaces\Models\Version;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildSiteStatsAction
{
    use AsAction;

    public function handle(string $period = 'last_30_days'): SiteStatsData
    {
        [$start, $end] = $this->resolveDateRange($period);
        [$prevStart, $prevEnd] = $this->resolvePriorPeriod($period);

        $totalViews = (int) AccessLog::query()
            ->whereBetween('viewed_at', [$start, $end])
            ->sum('visits');

        $totalVisitors = AccessLog::query()
            ->whereBetween('viewed_at', [$start, $end])
            ->distinct('session_id')
            ->count('session_id');

        $publishedCount = Version::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->count();

        $workQueueCount = Page::query()
            ->where('workspace_id', '>', 0)
            ->count();

        $prevViews = (int) AccessLog::query()
            ->whereBetween('viewed_at', [$prevStart, $prevEnd])
            ->sum('visits');

        $prevVisitors = AccessLog::query()
            ->whereBetween('viewed_at', [$prevStart, $prevEnd])
            ->distinct('session_id')
            ->count('session_id');

        return new SiteStatsData(
            totalViews: $totalViews,
            totalVisitors: $totalVisitors,
            workQueueCount: $workQueueCount,
            publishedCount: $publishedCount,
            sparklineViews: $this->buildSparkline($start, $end, 'views'),
            sparklineVisitors: $this->buildSparkline($start, $end, 'visitors'),
            sparklinePublished: $this->buildSparklinePublished($start, $end),
            viewsTrendPercent: $this->trendPercent($totalViews, $prevViews),
            visitorsTrendPercent: $this->trendPercent($totalVisitors, $prevVisitors),
        );
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function resolveDateRange(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->startOfDay(), $now],
            'this_week' => [$now->startOfWeek(), $now],
            'this_month' => [$now->startOfMonth(), $now],
            'this_year' => [$now->startOfYear(), $now],
            default => [$now->subDays(30)->startOfDay(), $now],
        };
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function resolvePriorPeriod(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'this_week' => [$now->subWeek()->startOfWeek(), $now->subWeek()->endOfWeek()],
            'this_month' => [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()],
            'this_year' => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            default => [$now->subDays(60)->startOfDay(), $now->subDays(30)],
        };
    }

    /**
     * Build a 7-point sparkline by splitting [$start, $end] into 7 equal buckets.
     *
     * @return list<int>
     */
    private function buildSparkline(CarbonImmutable $start, CarbonImmutable $end, string $type): array
    {
        $bucketSeconds = max(1, (int) ($start->diffInSeconds($end) / 7));
        $points = [];

        for ($bucket = 0; $bucket < 7; $bucket++) {
            $bucketStart = $start->addSeconds($bucket * $bucketSeconds);
            $bucketEnd = $start->addSeconds(($bucket + 1) * $bucketSeconds);

            $query = AccessLog::query()->whereBetween('viewed_at', [$bucketStart, $bucketEnd]);

            $points[] = $type === 'visitors'
                ? $query->distinct('session_id')->count('session_id')
                : (int) $query->sum('visits');
        }

        return $points;
    }

    /** @return list<int> */
    private function buildSparklinePublished(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $bucketSeconds = max(1, (int) ($start->diffInSeconds($end) / 7));
        $points = [];

        for ($bucket = 0; $bucket < 7; $bucket++) {
            $bucketStart = $start->addSeconds($bucket * $bucketSeconds);
            $bucketEnd = $start->addSeconds(($bucket + 1) * $bucketSeconds);

            $points[] = Version::query()
                ->whereNotNull('published_at')
                ->whereBetween('published_at', [$bucketStart, $bucketEnd])
                ->count();
        }

        return $points;
    }

    private function trendPercent(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
