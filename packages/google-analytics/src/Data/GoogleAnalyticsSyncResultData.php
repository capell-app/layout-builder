<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Data;

use Spatie\LaravelData\Data;

final class GoogleAnalyticsSyncResultData extends Data
{
    public function __construct(
        public readonly bool $synced,
        public readonly string $message,
        public readonly int $dailyRows = 0,
        public readonly int $pageRows = 0,
    ) {}
}
