<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Contracts;

use Capell\GA4Reports\Data\GA4ReportsDailyMetricData;
use Capell\GA4Reports\Data\GA4ReportsPageMetricData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;

interface GA4ReportsDataClientInterface
{
    public function isConfigured(): bool;

    /**
     * @return list<GA4ReportsDailyMetricData>
     */
    public function dailyMetrics(GA4ReportsWindowData $window): array;

    /**
     * @return list<GA4ReportsPageMetricData>
     */
    public function pageMetrics(GA4ReportsWindowData $window): array;
}
