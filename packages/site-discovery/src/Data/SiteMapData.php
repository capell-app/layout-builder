<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Data;

use Spatie\LaravelData\Data;

class SiteMapData extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public ?int $total = null,
    ) {}
}
