<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class RegistryHealthData extends Data
{
    /**
     * @param  DataCollection<int, RegistrySectionData>  $sections
     */
    public function __construct(
        public readonly DataCollection $sections,
    ) {}
}
