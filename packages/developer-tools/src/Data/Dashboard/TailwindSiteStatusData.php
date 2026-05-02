<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data\Dashboard;

use Spatie\LaravelData\Data;

final class TailwindSiteStatusData extends Data
{
    /**
     * @param  'fresh'|'stale'|'never_built'  $status
     */
    public function __construct(
        public readonly string $siteName,
        public readonly string $status,
        public readonly ?string $lastBuiltAt,
    ) {}
}
