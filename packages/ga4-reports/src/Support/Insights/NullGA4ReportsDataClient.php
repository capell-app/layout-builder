<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Support\Insights;

use Capell\GA4Reports\Contracts\GA4ReportsDataClientInterface;
use Capell\GA4Reports\Data\GA4ReportsWindowData;

final class NullGA4ReportsDataClient implements GA4ReportsDataClientInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function dailyMetrics(GA4ReportsWindowData $window): array
    {
        return [];
    }

    public function pageMetrics(GA4ReportsWindowData $window): array
    {
        return [];
    }
}
