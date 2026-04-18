<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Capell\Plugins\Enums\CapabilityWarningLevel;
use Spatie\LaravelData\Data;

final class CapabilityWarningData extends Data
{
    /** @param  list<string>  $warnings */
    public function __construct(
        public readonly CapabilityWarningLevel $highestLevel,
        public readonly array $warnings,
    ) {}
}
