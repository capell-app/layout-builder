<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Data\Dashboard;

use Spatie\LaravelData\Data;

final class RegistryEntryData extends Data
{
    public function __construct(
        public readonly string $class,
        public readonly string $sourcePackage,
        public readonly bool $autoDiscovered,
    ) {}
}
