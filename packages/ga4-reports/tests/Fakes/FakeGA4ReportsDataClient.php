<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Tests\Fakes;

use Capell\GA4Reports\Contracts\GA4ReportsDataClientInterface;
use Capell\GA4Reports\Data\GA4ReportsDailyMetricData;
use Capell\GA4Reports\Data\GA4ReportsPageMetricData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;
use RuntimeException;

final class FakeGA4ReportsDataClient implements GA4ReportsDataClientInterface
{
    /**
     * @param  list<GA4ReportsDailyMetricData>  $dailyMetrics
     * @param  list<GA4ReportsPageMetricData>  $pageMetrics
     */
    public function __construct(
        private readonly bool $configured,
        private readonly array $dailyMetrics = [],
        private readonly array $pageMetrics = [],
        private readonly bool $shouldFail = false,
    ) {}

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function dailyMetrics(GA4ReportsWindowData $window): array
    {
        if ($this->shouldFail) {
            throw new RuntimeException('GA4 client failed.');
        }

        return $this->dailyMetrics;
    }

    public function pageMetrics(GA4ReportsWindowData $window): array
    {
        return $this->pageMetrics;
    }
}
