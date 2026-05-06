<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Data;

use Spatie\LaravelData\Data;

final class GA4ReportsConfigData extends Data
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $propertyId,
        public readonly string $credentialsPath,
        public readonly int $syncDays,
        public readonly string $routeSlug,
    ) {}
}
