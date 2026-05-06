<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class TailwindBuildStatusData extends Data
{
    /**
     * @param  DataCollection<int, TailwindSiteStatusData>  $sites
     */
    public function __construct(
        public readonly DataCollection $sites,
        public readonly int $freshCount,
        public readonly int $staleCount,
        public readonly int $neverBuiltCount,
    ) {}
}
