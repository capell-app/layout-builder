<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;

final class CacheHealthData extends Data
{
    public function __construct(
        public readonly int $totalEnabledUrls,
        public readonly int $cachedCount,
        public readonly int $staleCount,
        public readonly int $missingCount,
        public readonly ?string $lastWarmedAt,
        public readonly int $siteId,
        public readonly string $siteName,
    ) {}
}
