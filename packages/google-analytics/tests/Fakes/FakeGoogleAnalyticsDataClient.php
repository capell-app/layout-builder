<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Tests\Fakes;

use Capell\GoogleAnalytics\Contracts\GoogleAnalyticsDataClientInterface;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsDailyMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsPageMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;

final class FakeGoogleAnalyticsDataClient implements GoogleAnalyticsDataClientInterface
{
    /**
     * @param  list<GoogleAnalyticsDailyMetricData>  $dailyMetrics
     * @param  list<GoogleAnalyticsPageMetricData>  $pageMetrics
     */
    public function __construct(
        private readonly bool $configured,
        private readonly array $dailyMetrics = [],
        private readonly array $pageMetrics = [],
    ) {}

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function dailyMetrics(GoogleAnalyticsWindowData $window): array
    {
        return $this->dailyMetrics;
    }

    public function pageMetrics(GoogleAnalyticsWindowData $window): array
    {
        return $this->pageMetrics;
    }
}
