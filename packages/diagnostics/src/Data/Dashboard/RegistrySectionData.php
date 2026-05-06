<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class RegistrySectionData extends Data
{
    /**
     * @param  DataCollection<int, RegistryEntryData>  $entries
     */
    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly DataCollection $entries,
    ) {}
}
