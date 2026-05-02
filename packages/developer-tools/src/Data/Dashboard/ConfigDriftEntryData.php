<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data\Dashboard;

use Spatie\LaravelData\Data;

final class ConfigDriftEntryData extends Data
{
    public function __construct(
        public readonly string $package,
        public readonly string $keyPath,
        /** @var 'missing'|'stale' */
        public readonly string $kind,
        public readonly ?string $shippedValue,
        public readonly ?string $hostValue,
    ) {}
}
