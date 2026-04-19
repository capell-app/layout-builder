<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Analytics;

interface AnalyticsProvider
{
    public function initScript(): string;

    public function enabled(): bool;
}
