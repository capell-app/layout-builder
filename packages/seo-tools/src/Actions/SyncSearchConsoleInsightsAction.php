<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\Contracts\SearchConsoleClientInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array{synced: int, configured: bool, pages: array<int, mixed>} run(int $siteId, int $limit = 10)
 */
final class SyncSearchConsoleInsightsAction
{
    use AsAction;

    /**
     * @return array{synced: int, configured: bool, pages: array<int, mixed>}
     */
    public function handle(int $siteId, int $limit = 10): array
    {
        $client = resolve(SearchConsoleClientInterface::class);

        if (! $client->isConfigured()) {
            return [
                'synced' => 0,
                'configured' => false,
                'pages' => [],
            ];
        }

        $metricRows = $client->urlMetricRows($siteId);

        $synced = 0;

        foreach ($metricRows as $metricRow) {
            $url = $metricRow['url'] ?? null;

            if (! is_string($url)) {
                continue;
            }

            if (trim($url) === '') {
                continue;
            }

            PersistSearchConsoleUrlMetricAction::run(
                siteId: $siteId,
                url: $url,
                windowStart: $this->dateMetric($metricRow['window_start'] ?? null),
                windowEnd: $this->dateMetric($metricRow['window_end'] ?? null),
                clicks: $this->integerMetric($metricRow, 'clicks'),
                impressions: $this->integerMetric($metricRow, 'impressions'),
                ctr: $this->floatMetric($metricRow, 'ctr'),
                averagePosition: $this->floatMetric($metricRow, 'average_position', 'position'),
                previousClicks: $this->integerMetric($metricRow, 'previous_clicks'),
                previousImpressions: $this->integerMetric($metricRow, 'previous_impressions'),
                previousCtr: $this->floatMetric($metricRow, 'previous_ctr'),
                previousAveragePosition: $this->floatMetric($metricRow, 'previous_average_position', 'previous_position'),
            );

            $synced++;
        }

        $pages = BuildDecliningSearchConsolePagesAction::run($siteId, $limit);

        return [
            'synced' => $synced,
            'configured' => true,
            'pages' => $pages,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function integerMetric(array $row, string $key): int
    {
        $value = $row[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function floatMetric(array $row, string $key, ?string $fallbackKey = null): float
    {
        $value = $row[$key] ?? ($fallbackKey === null ? 0.0 : ($row[$fallbackKey] ?? 0.0));

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function dateMetric(mixed $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return Date::parse($value);
        }

        return Date::now();
    }
}
