<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class SetupHealthData extends Data
{
    /**
     * @param  DataCollection<int, SetupCheckData>  $checks
     */
    public function __construct(
        public readonly DataCollection $checks,
        public readonly bool $allGreen,
    ) {}
}
