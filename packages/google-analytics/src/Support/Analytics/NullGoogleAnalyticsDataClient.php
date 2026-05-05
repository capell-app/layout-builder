<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Support\Analytics;

use Capell\GoogleAnalytics\Contracts\GoogleAnalyticsDataClientInterface;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;

final class NullGoogleAnalyticsDataClient implements GoogleAnalyticsDataClientInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function dailyMetrics(GoogleAnalyticsWindowData $window): array
    {
        return [];
    }

    public function pageMetrics(GoogleAnalyticsWindowData $window): array
    {
        return [];
    }
}
