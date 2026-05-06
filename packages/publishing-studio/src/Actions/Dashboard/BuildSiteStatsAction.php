<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\Dashboard;

use Capell\Admin\Data\Dashboard\SiteStatsData;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildSiteStatsAction
{
    use AsAction;

    public function handle(string $period = 'last_30_days'): SiteStatsData
    {
        [$start, $end] = $this->resolveDateRange($period);

        $publishedCount = Version::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->count();

        $workQueueCount = Page::query()
            ->where('workspace_id', '>', 0)
            ->count();

        $scheduledCount = Workspace::query()
            ->where('status', WorkspaceStatusEnum::Scheduled->value)
            ->count();

        return new SiteStatsData(
            workQueueCount: $workQueueCount,
            publishedCount: $publishedCount,
            sparklinePublished: $this->buildSparklinePublished($start, $end),
            pendingCount: $scheduledCount,
            expiredCount: 0,
            totalPagesCount: Page::query()->where('workspace_id', 0)->count(),
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
}
