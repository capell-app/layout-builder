<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\Models\SearchConsoleUrlMetric;
use Carbon\CarbonInterface;
use Lorisleiva\Actions\Concerns\AsAction;

final class PersistSearchConsoleUrlMetricAction
{
    use AsAction;

    public function handle(
        int $siteId,
        string $url,
        CarbonInterface $windowStart,
        CarbonInterface $windowEnd,
        int $clicks,
        int $impressions,
        float $ctr,
        float $averagePosition,
        int $previousClicks,
        int $previousImpressions,
        float $previousCtr,
        float $previousAveragePosition,
    ): SearchConsoleUrlMetric {
        $clickDelta = $clicks - $previousClicks;
        $impressionDelta = $impressions - $previousImpressions;
        $positionDelta = $averagePosition - $previousAveragePosition;

        return SearchConsoleUrlMetric::query()->updateOrCreate(
            [
                'site_id' => $siteId,
                'url_hash' => hash('sha256', $url),
                'window_start' => $windowStart->toDateString(),
                'window_end' => $windowEnd->toDateString(),
            ],
            [
                'url' => $url,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'average_position' => $averagePosition,
                'previous_clicks' => $previousClicks,
                'previous_impressions' => $previousImpressions,
                'previous_ctr' => $previousCtr,
                'previous_average_position' => $previousAveragePosition,
                'click_delta' => $clickDelta,
                'impression_delta' => $impressionDelta,
                'position_delta' => $positionDelta,
                'synced_at' => now(),
            ],
        );
    }
}
