<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\Models\SearchConsoleUrlMetric;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildDecliningSearchConsolePagesAction
{
    use AsAction;

    /**
     * @return array<int, array{url: string, clicks: int, previous_clicks: int, click_delta: int}>
     */
    public function handle(int $siteId, int $limit = 10): array
    {
        return SearchConsoleUrlMetric::query()
            ->decliningPages($siteId, $limit)
            ->get(['url', 'clicks', 'previous_clicks', 'click_delta'])
            ->map(fn (SearchConsoleUrlMetric $metric): array => [
                'url' => $metric->url,
                'clicks' => $metric->clicks,
                'previous_clicks' => $metric->previous_clicks,
                'click_delta' => $metric->click_delta,
            ])
            ->values()
            ->all();
    }
}
