<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Data;

use Spatie\LaravelData\Data;

class EmailHeaderData extends Data
{
    public function __construct(
        public string $name,
        public string $value,
    ) {}
}
