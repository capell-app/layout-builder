<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ConfigDriftData extends Data
{
    /**
     * @param  DataCollection<int, ConfigDriftEntryData>  $entries
     */
    public function __construct(
        public readonly DataCollection $entries,
        public readonly int $totalDriftCount,
        public readonly int $packagesChecked,
    ) {}
}
