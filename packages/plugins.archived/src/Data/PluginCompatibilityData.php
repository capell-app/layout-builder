<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Spatie\LaravelData\Data;

final class PluginCompatibilityData extends Data
{
    public function __construct(
        public readonly string $capell,
        public readonly string $php,
    ) {}
}
