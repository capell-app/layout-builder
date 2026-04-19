<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Analytics;

interface AnalyticsProvider
{
    public function renderInitScript(): string;

    public function isEnabled(): bool;
}
