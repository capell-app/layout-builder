<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Contracts;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsDailyMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsPageMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;

interface GoogleAnalyticsDataClientInterface
{
    public function isConfigured(): bool;

    /**
     * @return list<GoogleAnalyticsDailyMetricData>
     */
    public function dailyMetrics(GoogleAnalyticsWindowData $window): array;

    /**
     * @return list<GoogleAnalyticsPageMetricData>
     */
    public function pageMetrics(GoogleAnalyticsWindowData $window): array;
}
