<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Actions\Dashboard;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Page;
use Capell\DashboardReports\Data\Dashboard\PublishingTrendData;
use Capell\DashboardReports\Data\Dashboard\PublishingTrendPointData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildPublishingTrendAction
{
    use AsObject;

    public function handle(string $period = 'last_30_days'): PublishingTrendData
    {
        [$rangeStart, $rangeEnd] = $this->resolveDateRange($period);
        $bucketSeconds = max(1, (int) ($rangeStart->diffInSeconds($rangeEnd) / 7));
        $points = [];

        for ($bucket = 0; $bucket < 7; $bucket++) {
            $bucketStart = $rangeStart->addSeconds($bucket * $bucketSeconds);
            $bucketEnd = $rangeStart->addSeconds(($bucket + 1) * $bucketSeconds);

            $points[] = new PublishingTrendPointData(
                label: $bucketStart->format('M j'),
                publishedCount: $this->publishedWithin($bucketStart, $bucketEnd),
                scheduledCount: $this->scheduledWithin($bucketStart, $bucketEnd),
            );
        }

        return new PublishingTrendData(
            points: $points,
            totalPublished: collect($points)->sum(fn (PublishingTrendPointData $point): int => $point->publishedCount),
            totalScheduled: $this->basePageQuery()->pending()->count(),
        );
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function resolveDateRange(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'this_week' => [$now->startOfWeek(), $now->endOfWeek()],
            'this_month' => [$now->startOfMonth(), $now->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            default => [$now->subDays(30)->startOfDay(), $now->endOfDay()],
        };
    }

    private function publishedWithin(CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): int
    {
        return $this->basePageQuery()
            ->publishedDate()
            ->where(fn (Builder $query): Builder => $this->publishedMarkerWithin($query, $rangeStart, $rangeEnd))
            ->count();
    }

    private function scheduledWithin(CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): int
    {
        return $this->basePageQuery()
            ->pending()
            ->whereBetween((new Page)->qualifyColumn('visible_from'), [$rangeStart, $rangeEnd])
            ->count();
    }

    private function publishedMarkerWithin(Builder $query, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Builder
    {
        return $query
            ->whereBetween($query->getModel()->qualifyColumn('visible_from'), [$rangeStart, $rangeEnd])
            ->orWhere(function (Builder $fallbackQuery) use ($rangeStart, $rangeEnd): void {
                $fallbackQuery
                    ->whereNull($fallbackQuery->getModel()->qualifyColumn('visible_from'))
                    ->whereBetween($fallbackQuery->getModel()->qualifyColumn('created_at'), [$rangeStart, $rangeEnd]);
            });
    }

    /**
     * @return Builder<Page>
     */
    private function basePageQuery(): Builder
    {
        /** @var Builder<Page> $query */
        $query = SiteScope::applyForCurrentActor(Page::query());

        return $query;
    }
}
